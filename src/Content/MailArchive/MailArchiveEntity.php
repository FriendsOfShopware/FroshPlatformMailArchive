<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Flow\FlowEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class MailArchiveEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var array<string, string>
     */
    protected array $sender;

    /**
     * @var array<string, string>
     */
    protected array $receiver;

    protected string $subject;

    protected ?string $plainText = null;

    protected ?string $htmlText = null;

    protected ?string $transportState = null;

    protected ?string $emlPath = null;

    protected ?string $salesChannelId = null;

    protected ?SalesChannelEntity $salesChannel = null;

    protected ?string $customerId = null;

    protected ?CustomerEntity $customer = null;

    protected ?string $orderId = null;

    protected ?string $orderVersionId = null;

    protected ?OrderEntity $order = null;

    protected ?string $flowId = null;

    protected ?FlowEntity $flow = null;

    /**
     * @var EntityCollection<MailArchiveAttachmentEntity>|null
     */
    protected ?EntityCollection $attachments = null;

    protected ?string $sourceMailId = null;

    protected ?MailArchiveEntity $sourceMail = null;

    /**
     * @var EntityCollection<MailArchiveEntity>|null
     */
    protected ?EntityCollection $sourceMails = null;

    protected ?string $mailTemplateId = null;

    protected ?MailTemplateEntity $mailTemplate = null;

    /**
     * @return array<string, string>
     */
    public function getSender(): array
    {
        return $this->sender;
    }

    /**
     * @param array<string, string> $sender
     */
    public function setSender(array $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return array<string, string>
     */
    public function getReceiver(): array
    {
        return $this->receiver;
    }

    /**
     * @param array<string, string> $receiver
     */
    public function setReceiver(array $receiver): void
    {
        $this->receiver = $receiver;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getPlainText(): ?string
    {
        return $this->plainText;
    }

    public function setPlainText(?string $plainText): void
    {
        $this->plainText = $plainText;
    }

    public function getHtmlText(): ?string
    {
        return $this->htmlText;
    }

    public function setHtmlText(?string $htmlText): void
    {
        $this->htmlText = $htmlText;
    }

    public function getEmlPath(): ?string
    {
        return $this->emlPath;
    }

    public function setEmlPath(?string $emlPath): void
    {
        $this->emlPath = $emlPath;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return EntityCollection<MailArchiveAttachmentEntity>|null
     */
    public function getAttachments(): ?EntityCollection
    {
        return $this->attachments;
    }

    /**
     * @param EntityCollection<MailArchiveAttachmentEntity> $attachments
     */
    public function setAttachments(EntityCollection $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function getSourceMailId(): ?string
    {
        return $this->sourceMailId;
    }

    public function setSourceMailId(string $sourceMailId): void
    {
        $this->sourceMailId = $sourceMailId;
    }

    public function getSourceMail(): ?MailArchiveEntity
    {
        return $this->sourceMail;
    }

    public function setSourceMail(MailArchiveEntity $sourceMail): void
    {
        $this->sourceMail = $sourceMail;
    }

    public function getTransportState(): ?string
    {
        return $this->transportState;
    }

    public function setTransportState(string $transportState): void
    {
        $this->transportState = $transportState;
    }

    /**
     * @return EntityCollection<MailArchiveEntity>|null
     */
    public function getSourceMails(): ?EntityCollection
    {
        return $this->sourceMails;
    }

    /**
     * @param EntityCollection<MailArchiveEntity> $sourceMails
     */
    public function setSourceMails(EntityCollection $sourceMails): void
    {
        $this->sourceMails = $sourceMails;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    public function getFlowId(): ?string
    {
        return $this->flowId;
    }

    public function setFlowId(?string $flowId): void
    {
        $this->flowId = $flowId;
    }

    public function getFlow(): ?FlowEntity
    {
        return $this->flow;
    }

    public function setFlow(?FlowEntity $flow): void
    {
        $this->flow = $flow;
    }

    public function getOrderVersionId(): ?string
    {
        return $this->orderVersionId;
    }

    public function setOrderVersionId(?string $orderVersionId): void
    {
        $this->orderVersionId = $orderVersionId;
    }

    public function getMailTemplateId(): ?string
    {
        return $this->mailTemplateId;
    }

    public function setMailTemplateId(?string $mailTemplateId): void
    {
        $this->mailTemplateId = $mailTemplateId;
    }

    public function getMailTemplate(): ?MailTemplateEntity
    {
        return $this->mailTemplate;
    }

    public function setMailTemplate(?MailTemplateEntity $mailTemplate): void
    {
        $this->mailTemplate = $mailTemplate;
    }
}
