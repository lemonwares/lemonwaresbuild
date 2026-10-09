<?php

/*
|--------------------------------------------------------------------------
| WHMCS API actions the site may call
|--------------------------------------------------------------------------
| Whmcs::call() refuses anything not listed here. Adding a WHMCS feature is a one-line entry.
|
| group     Area of WHMCS, used by the admin console and the audit log.
| write     True when the action changes data in WHMCS (audited, never cached).
| required  Parameters WHMCS always needs; checked before the request is sent.
| sensitive True when the request or response carries secrets (never logged in full).
|
| Required parameters follow the WHMCS API reference; check new entries against the
| documentation for the WHMCS version you run.
*/

return [

    // Clients and users
    'GetClients' => ['group' => 'clients'],
    'GetClientsDetails' => ['group' => 'clients'],
    'AddClient' => ['group' => 'clients', 'write' => true, 'required' => ['firstname', 'lastname', 'email']],
    'UpdateClient' => ['group' => 'clients', 'write' => true, 'required' => ['clientid']],
    'CloseClient' => ['group' => 'clients', 'write' => true, 'required' => ['clientid']],
    'DeleteClient' => ['group' => 'clients', 'write' => true, 'required' => ['clientid']],
    'GetContacts' => ['group' => 'clients'],
    'AddContact' => ['group' => 'clients', 'write' => true, 'required' => ['clientid']],
    'UpdateContact' => ['group' => 'clients', 'write' => true, 'required' => ['contactid']],
    'DeleteContact' => ['group' => 'clients', 'write' => true, 'required' => ['contactid']],
    'GetCredits' => ['group' => 'clients', 'required' => ['clientid']],
    'AddCredit' => ['group' => 'clients', 'write' => true, 'required' => ['clientid', 'description', 'amount']],
    'GetEmails' => ['group' => 'clients', 'required' => ['clientid']],
    'SendEmail' => ['group' => 'clients', 'write' => true],
    'GetUsers' => ['group' => 'clients'],
    'AddUser' => ['group' => 'clients', 'write' => true, 'required' => ['firstname', 'lastname', 'email'], 'sensitive' => true],
    'ResetPassword' => ['group' => 'clients', 'write' => true],
    'ValidateLogin' => ['group' => 'clients', 'required' => ['email'], 'sensitive' => true],
    'CreateSsoToken' => ['group' => 'clients', 'write' => true, 'sensitive' => true],
    'ModuleSingleSignOn' => ['group' => 'services', 'write' => true, 'sensitive' => true],

    // Services (hosting, VPS) and add-ons
    'GetClientsProducts' => ['group' => 'services'],
    'GetClientsAddons' => ['group' => 'services'],
    'UpdateClientProduct' => ['group' => 'services', 'write' => true, 'required' => ['serviceid']],
    'UpgradeProduct' => ['group' => 'services', 'write' => true, 'required' => ['serviceid', 'type']],
    'ModuleCreate' => ['group' => 'services', 'write' => true, 'required' => ['serviceid']],
    'ModuleSuspend' => ['group' => 'services', 'write' => true, 'required' => ['serviceid']],
    'ModuleUnsuspend' => ['group' => 'services', 'write' => true, 'required' => ['serviceid']],
    'ModuleTerminate' => ['group' => 'services', 'write' => true, 'required' => ['serviceid']],
    'ModuleChangePackage' => ['group' => 'services', 'write' => true, 'required' => ['serviceid']],
    'ModuleChangePw' => ['group' => 'services', 'write' => true, 'required' => ['serviceid'], 'sensitive' => true],

    // Domains
    'GetClientsDomains' => ['group' => 'domains'],
    'UpdateClientDomain' => ['group' => 'domains', 'write' => true, 'required' => ['domainid']],
    'DomainWhois' => ['group' => 'domains', 'required' => ['domain']],
    'DomainRegister' => ['group' => 'domains', 'write' => true],
    'DomainTransfer' => ['group' => 'domains', 'write' => true],
    'DomainRenew' => ['group' => 'domains', 'write' => true],
    'DomainGetNameservers' => ['group' => 'domains', 'required' => ['domainid']],
    'DomainUpdateNameservers' => ['group' => 'domains', 'write' => true, 'required' => ['ns1', 'ns2']],
    'DomainGetLockingStatus' => ['group' => 'domains', 'required' => ['domainid']],
    'DomainUpdateLocking' => ['group' => 'domains', 'write' => true],
    'DomainRequestEPP' => ['group' => 'domains', 'write' => true, 'required' => ['domainid'], 'sensitive' => true],
    'DomainGetWhoisInfo' => ['group' => 'domains', 'required' => ['domainid']],
    'DomainUpdateWhoisInfo' => ['group' => 'domains', 'write' => true, 'required' => ['domainid']],
    'DomainToggleIdProtect' => ['group' => 'domains', 'write' => true, 'required' => ['domainid']],
    'GetTLDPricing' => ['group' => 'domains'],

    // Billing: invoices, payments, transactions, quotes
    'GetInvoices' => ['group' => 'billing'],
    'GetInvoice' => ['group' => 'billing', 'required' => ['invoiceid']],
    'CreateInvoice' => ['group' => 'billing', 'write' => true, 'required' => ['userid']],
    'UpdateInvoice' => ['group' => 'billing', 'write' => true, 'required' => ['invoiceid']],
    'AddInvoicePayment' => ['group' => 'billing', 'write' => true, 'required' => ['invoiceid', 'transid', 'gateway']],
    'CapturePayment' => ['group' => 'billing', 'write' => true, 'required' => ['invoiceid']],
    'ApplyCredit' => ['group' => 'billing', 'write' => true, 'required' => ['invoiceid', 'amount']],
    'GenInvoices' => ['group' => 'billing', 'write' => true],
    'AddBillableItem' => ['group' => 'billing', 'write' => true, 'required' => ['clientid', 'description', 'amount']],
    'GetTransactions' => ['group' => 'billing'],
    'AddTransaction' => ['group' => 'billing', 'write' => true, 'required' => ['paymentmethod']],
    'UpdateTransaction' => ['group' => 'billing', 'write' => true, 'required' => ['transactionid']],
    'GetPayMethods' => ['group' => 'billing', 'required' => ['clientid']],
    'GetQuotes' => ['group' => 'billing'],
    'CreateQuote' => ['group' => 'billing', 'write' => true, 'required' => ['subject', 'stage', 'validuntil']],
    'UpdateQuote' => ['group' => 'billing', 'write' => true, 'required' => ['quoteid']],
    'SendQuote' => ['group' => 'billing', 'write' => true, 'required' => ['quoteid']],
    'AcceptQuote' => ['group' => 'billing', 'write' => true, 'required' => ['quoteid']],
    'DeleteQuote' => ['group' => 'billing', 'write' => true, 'required' => ['quoteid']],

    // Orders and products
    'GetOrders' => ['group' => 'orders'],
    'GetOrderStatuses' => ['group' => 'orders'],
    'AddOrder' => ['group' => 'orders', 'write' => true, 'required' => ['clientid', 'paymentmethod']],
    'AcceptOrder' => ['group' => 'orders', 'write' => true, 'required' => ['orderid']],
    'CancelOrder' => ['group' => 'orders', 'write' => true, 'required' => ['orderid']],
    'PendingOrder' => ['group' => 'orders', 'write' => true, 'required' => ['orderid']],
    'FraudOrder' => ['group' => 'orders', 'write' => true, 'required' => ['orderid']],
    'DeleteOrder' => ['group' => 'orders', 'write' => true, 'required' => ['orderid']],
    'GetProducts' => ['group' => 'orders'],
    'GetPromotions' => ['group' => 'orders'],
    'GetPaymentMethods' => ['group' => 'orders'],
    'GetCurrencies' => ['group' => 'orders'],

    // Support
    'GetTickets' => ['group' => 'support'],
    'GetTicket' => ['group' => 'support'],
    'OpenTicket' => ['group' => 'support', 'write' => true, 'required' => ['deptid', 'subject', 'message']],
    'AddTicketReply' => ['group' => 'support', 'write' => true, 'required' => ['ticketid', 'message']],
    'AddTicketNote' => ['group' => 'support', 'write' => true, 'required' => ['ticketid', 'message']],
    'UpdateTicket' => ['group' => 'support', 'write' => true, 'required' => ['ticketid']],
    'MergeTicket' => ['group' => 'support', 'write' => true, 'required' => ['ticketid', 'mergeticketids']],
    'DeleteTicket' => ['group' => 'support', 'write' => true, 'required' => ['ticketid']],
    'GetSupportDepartments' => ['group' => 'support'],
    'GetSupportStatuses' => ['group' => 'support'],
    'GetTicketPredefinedReplies' => ['group' => 'support'],

    // System
    'WhmcsDetails' => ['group' => 'system'],
    'GetStats' => ['group' => 'system'],
    'GetActivityLog' => ['group' => 'system'],
    'GetAdminDetails' => ['group' => 'system'],
    'GetToDoItems' => ['group' => 'system'],
    'GetAutomationLog' => ['group' => 'system'],
    'GetEmailTemplates' => ['group' => 'system'],
    'GetConfigurationValue' => ['group' => 'system', 'required' => ['setting']],
    'LogActivity' => ['group' => 'system', 'write' => true, 'required' => ['description']],

];
