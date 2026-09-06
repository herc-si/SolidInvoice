<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\ElectronicInvoicingBundle\Action;

use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Augias\ElectronicInvoicingBundle\Repository\ElectronicInvoiceReceiptRepository;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;
use function sprintf;

/**
 * Serves the locally-stored document for a received electronic invoice — the
 * inbound counterpart of {@see \Augias\CoreBundle\Export\Action\DownloadExport},
 * whose realpath traversal guard is mirrored here for the same reason: the
 * stored path always comes from {@see \Augias\ElectronicInvoicingBundle\Manager\ElectronicInvoiceReceiptManager},
 * but a malformed value must never be able to serve an arbitrary file.
 *
 * Tenant isolation is enforced by ElectronicInvoiceReceiptRepository::find()
 * going through the global company Doctrine filter, the same as every other
 * company-scoped lookup in the app — a receipt belonging to another company
 * simply isn't found, the same 404 as a non-existent id.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class DownloadIncomingInvoice
{
    public function __construct(
        private ElectronicInvoiceReceiptRepository $receiptRepository,
        private string $projectDir,
    ) {
    }

    public function __invoke(string $id): BinaryFileResponse
    {
        $receipt = $this->receiptRepository->find($this->parseUlid($id));

        if (! $receipt instanceof ElectronicInvoiceReceipt || ! $receipt->hasDocument()) {
            throw new NotFoundHttpException();
        }

        $documentPath = (string) $receipt->getDocumentPath();
        $absolutePath = $this->projectDir . '/' . $documentPath;

        $storageRoot = realpath($this->projectDir . '/var/einvoicing/incoming');
        $resolved = realpath($absolutePath);

        if ($storageRoot === false || $resolved === false || ! str_starts_with($resolved, $storageRoot . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('invoice-%s.%s', $receipt->getInvoiceNumber() ?? $receipt->getExternalReference(), pathinfo($absolutePath, PATHINFO_EXTENSION)),
        );
        $response->headers->set('Content-Type', $receipt->getDocumentMimeType() ?? 'application/octet-stream');

        return $response;
    }

    private function parseUlid(string $id): Ulid
    {
        try {
            return Ulid::fromString($id);
        } catch (InvalidArgumentException) {
            throw new NotFoundHttpException();
        }
    }
}
