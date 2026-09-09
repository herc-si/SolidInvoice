import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

type ZoneName = 'top' | 'left_column' | 'right_column';

interface LayoutPayload {
    widgets: { id: string; zone: ZoneName }[];
    hidden: string[];
}

/**
 * Edit mode for the dashboard: drag, reorder, remove and re-add widgets.
 *
 * Nothing here mutates a widget's content. Cards are moved between the zones and
 * a hidden stash, and the resulting arrangement is POSTed as a whole document.
 * That keeps the client dumb: it never has to know what a widget *is*, only
 * where it sits, so a new widget needs no change to this file.
 */
export default class extends Controller<HTMLElement> {
    static targets = [
        'zone',
        'widget',
        'controls',
        'picker',
        'pickerItems',
        'pickerEmpty',
        'stash',
        'status',
        'editToggle',
        'editToggleLabel',
        'resetForm',
    ];

    static values = { saveUrl: String, csrfToken: String };
    static classes = ['editing'];

    declare readonly zoneTargets: HTMLElement[];
    declare readonly controlsTargets: HTMLElement[];
    declare readonly pickerTarget: HTMLElement;
    declare readonly pickerItemsTarget: HTMLElement;
    declare readonly pickerEmptyTarget: HTMLElement;
    declare readonly stashTarget: HTMLElement;
    declare readonly statusTarget: HTMLElement;
    declare readonly editToggleTarget: HTMLButtonElement;
    declare readonly editToggleLabelTarget: HTMLElement;
    declare readonly resetFormTarget: HTMLElement;
    declare readonly saveUrlValue: string;
    declare readonly csrfTokenValue: string;
    declare readonly editingClass: string;

    private sortables: Sortable[] = [];
    private editing = false;
    private saveTimer: number | null = null;

    connect(): void {
        this.refreshPickerEmptyState();
    }

    disconnect(): void {
        this.teardownSortables();

        if (this.saveTimer !== null) {
            window.clearTimeout(this.saveTimer);
            this.saveTimer = null;
        }
    }

    toggleEdit(): void {
        this.editing = !this.editing;

        this.element.classList.toggle(this.editingClass, this.editing);
        this.editToggleTarget.setAttribute('aria-pressed', String(this.editing));

        const label = this.editToggleLabelTarget;
        label.textContent =
            (this.editing ? label.dataset.doneLabel : label.dataset.customiseLabel) ?? label.textContent;

        this.pickerTarget.hidden = !this.editing;
        this.resetFormTarget.hidden = !this.editing;
        this.controlsTargets.forEach((controls) => {
            controls.hidden = !this.editing;
        });

        if (this.editing) {
            this.setupSortables();
        } else {
            this.teardownSortables();
            this.clearStatus();
        }
    }

    remove(event: Event): void {
        const widget = this.widgetFrom(event);

        // Pinned widgets have no remove button, but a stale DOM or a devtools
        // click should not be able to make one disappear either.
        if (!widget || widget.dataset.widgetRemovable !== 'true') {
            return;
        }

        this.stashTarget.append(widget);
        this.addPickerItem(widget);
        this.refreshPickerEmptyState();
        this.scheduleSave();
    }

    add(event: Event): void {
        const button = (event.target as HTMLElement).closest<HTMLElement>('[data-widget-id]');

        if (!button) {
            return;
        }

        const id = button.dataset.widgetId ?? '';
        const zone = (button.dataset.widgetZone ?? 'left_column') as ZoneName;
        const stashed = this.stashTarget.querySelector<HTMLElement>(`[data-widget-id="${CSS.escape(id)}"]`);

        button.remove();
        this.refreshPickerEmptyState();

        if (stashed) {
            stashed.dataset.widgetZone = zone;
            this.zoneElement(zone)?.append(stashed);
            this.scheduleSave();

            return;
        }

        // Hidden before this page was rendered, so its markup was never
        // generated: the server has to run the widget's queries to produce it.
        // Save first so the reload reads the layout we just described.
        void this.save().then(() => window.location.reload());
    }

    moveUp(event: Event): void {
        const widget = this.widgetFrom(event);
        const previous = widget?.previousElementSibling;

        if (widget && previous) {
            previous.before(widget);
            this.scheduleSave();
        }
    }

    moveDown(event: Event): void {
        const widget = this.widgetFrom(event);
        const next = widget?.nextElementSibling;

        if (widget && next) {
            next.after(widget);
            this.scheduleSave();
        }
    }

    private setupSortables(): void {
        this.teardownSortables();

        this.sortables = this.zoneTargets.map((zone) =>
            Sortable.create(zone, {
                group: 'dashboard',
                handle: '[data-handle]',
                draggable: '.dashboard-widget',
                animation: 150,
                ghostClass: 'dashboard-widget--ghost',
                onEnd: ({ item, to }) => {
                    // The card's own record of where it lives has to follow it,
                    // because the payload is built from these attributes and not
                    // from the DOM position alone.
                    item.dataset.widgetZone = (to as HTMLElement).dataset.zone ?? '';
                    this.scheduleSave();
                },
            }),
        );
    }

    private teardownSortables(): void {
        this.sortables.forEach((sortable) => sortable.destroy());
        this.sortables = [];
    }

    private zoneElement(zone: ZoneName): HTMLElement | undefined {
        return this.zoneTargets.find((element) => element.dataset.zone === zone);
    }

    private widgetFrom(event: Event): HTMLElement | null {
        return (event.target as HTMLElement).closest<HTMLElement>('.dashboard-widget');
    }

    /**
     * Build a picker entry out of the card being removed.
     *
     * The name and icon are cloned from the card's own controls rather than
     * looked up, so the picker never needs a second source of truth for what a
     * widget is called.
     */
    private addPickerItem(widget: HTMLElement): void {
        const id = widget.dataset.widgetId ?? '';

        if (this.pickerItemsTarget.querySelector(`[data-widget-id="${CSS.escape(id)}"]`)) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dashboard-picker-item';
        button.dataset.widgetId = id;
        button.dataset.widgetZone = widget.dataset.widgetZone ?? 'left_column';
        button.dataset.action = 'dashboard-layout#add';

        const name = widget.querySelector('.dashboard-widget-name');

        if (name) {
            button.append(...Array.from(name.cloneNode(true).childNodes));
        }

        this.pickerItemsTarget.append(button);
    }

    private refreshPickerEmptyState(): void {
        this.pickerEmptyTarget.hidden = this.pickerItemsTarget.children.length > 0;
    }

    /**
     * Coalesce the burst of changes a drag produces into one request.
     */
    private scheduleSave(): void {
        if (this.saveTimer !== null) {
            window.clearTimeout(this.saveTimer);
        }

        this.saveTimer = window.setTimeout(() => {
            this.saveTimer = null;
            void this.save();
        }, 400);
    }

    private async save(): Promise<void> {
        this.setStatus(this.statusTarget.dataset.savingLabel ?? '', false);

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfTokenValue,
                },
                body: JSON.stringify(this.payload()),
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`${response.status} ${response.statusText}`);
            }

            this.setStatus(this.statusTarget.dataset.savedLabel ?? '', false);
        } catch (error) {
            // The arrangement on screen is now ahead of what is stored, and the
            // user is the only one who can tell whether that matters. Saying so
            // beats a silent console line they will never see.
            console.error('Unable to save the dashboard layout:', error);
            this.setStatus(this.statusTarget.dataset.errorLabel ?? '', true);
        }
    }

    private payload(): LayoutPayload {
        const widgets: LayoutPayload['widgets'] = [];

        this.zoneTargets.forEach((zone) => {
            const name = (zone.dataset.zone ?? '') as ZoneName;

            zone.querySelectorAll<HTMLElement>('.dashboard-widget').forEach((widget) => {
                widgets.push({ id: widget.dataset.widgetId ?? '', zone: name });
            });
        });

        const hidden = Array.from(
            this.pickerItemsTarget.querySelectorAll<HTMLElement>('[data-widget-id]'),
            (item) => item.dataset.widgetId ?? '',
        );

        return { widgets, hidden };
    }

    private setStatus(message: string, isError: boolean): void {
        this.statusTarget.textContent = message;
        this.statusTarget.classList.toggle('dashboard-toolbar-status--error', isError);
    }

    private clearStatus(): void {
        this.setStatus('', false);
    }
}
