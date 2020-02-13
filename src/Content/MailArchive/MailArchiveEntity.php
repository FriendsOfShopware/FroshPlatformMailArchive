<?php


namespace Frosh\MailArchive\Content\MailArchive;


use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class MailArchiveEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $sender;

    /**
     * @var array
     */
    protected $receiver;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $plainText;

    /**
     * @var string
     */
    protected $htmlText;

    /**
     * @var string
     */
    protected $eml;

    /**
     * @var string|null
     */
    protected $salesChannelId;

    /**
     * @var SalesChannelEntity|null
     */
    protected $salesChannel;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @var CustomerEntity
     */
    protected $customer;

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    public function getReceiver(): array
    {
        return $this->receiver;
    }

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

    public function getPlainText(): string
    {
        return $this->plainText;
    }

    public function setPlainText(string $plainText): void
    {
        $this->plainText = $plainText;
    }

    public function getHtmlText(): string
    {
        return $this->htmlText;
    }

    public function setHtmlText(string $htmlText): void
    {
        $this->htmlText = $htmlText;
    }

    public function getEml(): string
    {
        return $this->eml;
    }

    public function setEml(string $eml): void
    {
        $this->eml = $eml;
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

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }
}
