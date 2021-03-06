<?php

namespace NordeaPayment\TransactionInformation;

use DOMDocument;
use InvalidArgumentException;
use NordeaPayment\AccountInterface;
use NordeaPayment\BIC;
use NordeaPayment\FinancialInstitutionInterface;
use NordeaPayment\IID;
use NordeaPayment\Money;
use NordeaPayment\PaymentInformation\PaymentInformation;
use NordeaPayment\PostalAddressInterface;

/**
 * BankCreditTransfer contains all the information about a type 3 transaction.
 */
class BankCreditTransfer extends CreditTransfer
{
    /**
     * @var IBAN|BBAN
     */
    protected $creditorAccount;

    /**
     * @var FinancialInstitutionInterface
     */
    protected $creditorAgent;

    /**
     * {@inheritdoc}
     *
     * @param IBAN|BBAN $creditorAccount IBAN or BBAN from creditor.
     * @param BIC|IID $creditorAgent BIC or IID of the creditor's financial institution
     *
     * @throws \InvalidArgumentException When the amount is not in EUR or DKK or when
     * the creditor agent is not BIC or IID.
     */
    public function __construct($endToEndId, Money\Money $amount, $creditorName, PostalAddressInterface $creditorAddress, AccountInterface $creditorAccount, FinancialInstitutionInterface $creditorAgent)
    {
        if (!$amount instanceof Money\EUR && !$amount instanceof Money\DKK) {
            throw new InvalidArgumentException(sprintf(
                'The amount must be an instance of Money\EUR or Money\DKK (instance of %s given).',
                get_class($amount)
            ));
        }

        if (!$creditorAgent instanceof BIC && !$creditorAgent instanceof IID) {
            throw new InvalidArgumentException('The creditor agent must be an instance of BIC or IID.');
        }

        parent::__construct($endToEndId, $amount, $creditorName, $creditorAddress);

        $this->creditorAccount = $creditorAccount;
        $this->creditorAgent = $creditorAgent;
    }

    /**
     * {@inheritdoc}
     */
    public function asDom(DOMDocument $doc, PaymentInformation $paymentInformation)
    {
        $root = $this->buildHeader($doc, $paymentInformation);

        $creditorAgent = $doc->createElement('CdtrAgt');
        $creditorAgent->appendChild($this->creditorAgent->asDom($doc));
        $root->appendChild($creditorAgent);

        $root->appendChild($this->buildCreditor($doc));

        $creditorAccount = $doc->createElement('CdtrAcct');
        $creditorAccount->appendChild($this->creditorAccount->asDom($doc));
        $root->appendChild($creditorAccount);

        $this->appendPurpose($doc, $root);

        $this->appendRemittanceInformation($doc, $root);

        return $root;
    }
}
