<?php

namespace App\Enums;

/**
 * Application permissions.
 *
 * Permissions follow a `<module>.<action>` naming convention. They are
 * persisted in the `permissions` table and compared literally, so renaming a
 * case here is a breaking change for existing data.
 */
enum Permission: string
{
    // Clients
    case ViewClients = 'clients.view';
    case CreateClients = 'clients.create';
    case UpdateClients = 'clients.update';
    case DeleteClients = 'clients.delete';
    case ExportClients = 'clients.export';

    // Client contract persons
    case ViewClientContactPersons = 'client-contact-persons.view';
    case CreateClientContactPersons = 'client-contact-persons.create';
    case UpdateClientContactPersons = 'client-contact-persons.update';
    case DeleteClientContactPersons = 'client-contact-persons.delete';

    // Client agents
    case ViewClientAgents = 'client-agents.view';
    case CreateClientAgents = 'client-agents.create';
    case UpdateClientAgents = 'client-agents.update';
    case DeleteClientAgents = 'client-agents.delete';

    // Employees
    case ViewEmployees = 'employees.view';
    case CreateEmployees = 'employees.create';
    case UpdateEmployees = 'employees.update';
    case DeleteEmployees = 'employees.delete';
    case ManageEmployeeAccounts = 'employees.manage-accounts';

    // Inventory
    case ViewInventory = 'inventory.view';
    case CreateInventory = 'inventory.create';
    case UpdateInventory = 'inventory.update';
    case DeleteInventory = 'inventory.delete';
    case ImportInventory = 'inventory.import';
    case ExportInventory = 'inventory.export';

    // Inventory categories
    case ViewCategories = 'categories.view';
    case CreateCategories = 'categories.create';
    case UpdateCategories = 'categories.update';
    case DeleteCategories = 'categories.delete';

    // Inventory subcategories
    case ViewSubcategories = 'subcategories.view';
    case CreateSubcategories = 'subcategories.create';
    case UpdateSubcategories = 'subcategories.update';
    case DeleteSubcategories = 'subcategories.delete';

    // Withdrawal slips
    case ViewWithdrawalSlips = 'withdrawal-slips.view';
    case CreateWithdrawalSlips = 'withdrawal-slips.create';
    case DeleteWithdrawalSlips = 'withdrawal-slips.delete';

    // Job orders
    case ViewJobOrders = 'job-orders.view';
    case CreateJobOrders = 'job-orders.create';
    case UpdateJobOrders = 'job-orders.update';
    case ArchiveJobOrders = 'job-orders.archive';
    case RestoreJobOrders = 'job-orders.restore';
    case ExportJobOrders = 'job-orders.export';
    case ImportJobOrders = 'job-orders.import';

    // Job order classes
    case ViewProductClasses = 'product-classes.view';
    case CreateProductClasses = 'product-classes.create';
    case UpdateProductClasses = 'product-classes.update';
    case ArchiveProductClasses = 'product-classes.archive';
    case RestoreProductClasses = 'product-classes.restore';

    // Job order subclasses
    case ViewProductSubclasses = 'product-subclasses.view';
    case CreateProductSubclasses = 'product-subclasses.create';
    case UpdateProductSubclasses = 'product-subclasses.update';
    case ArchiveProductSubclasses = 'product-subclasses.archive';
    case RestoreProductSubclasses = 'product-subclasses.restore';

    // Job order agents
    case ViewJobOrderAgents = 'job-order-agents.view';
    case CreateJobOrderAgents = 'job-order-agents.create';
    case UpdateJobOrderAgents = 'job-order-agents.update';
    case DeleteJobOrderAgents = 'job-order-agents.delete';

    // Cash receipts
    case ViewCashReceipts = 'cash-receipts.view';
    case CreateCashReceipts = 'cash-receipts.create';
    case UpdateCashReceipts = 'cash-receipts.update';
    case DeleteCashReceipts = 'cash-receipts.delete';
    case ExportCashReceipts = 'cash-receipts.export';

    // Payment options
    case ViewPaymentOptions = 'payment-options.view';
    case CreatePaymentOptions = 'payment-options.create';
    case UpdatePaymentOptions = 'payment-options.update';
    case DeletePaymentOptions = 'payment-options.delete';

    // Reports
    case ViewAccountsReceivable = 'reports.accounts-receivable';
    case ExportAccountsReceivable = 'reports.accounts-receivable.export';

    // Calendar
    case ViewAllTasks = 'calendar.view-all-tasks';
    case ViewOwnTasks = 'calendar.view-own-tasks';

    // Administration
    case ExecuteAdminTools = 'admin-tools.execute';
    case ViewActivityLog = 'activity-log.view';
    case ManageBackups = 'backups.manage';
    case ManageUserAccounts = 'users.manage';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Permissions granted to the staff role.
     *
     * @return array<int, Permission>
     */
    public static function staffDefaults(): array
    {
        return [
            self::ViewClients,
            self::CreateClients,
            self::UpdateClients,
            self::ViewClientContactPersons,
            self::CreateClientContactPersons,
            self::UpdateClientContactPersons,
            self::ViewClientAgents,
            self::ViewEmployees,
            self::ViewInventory,
            self::ViewCategories,
            self::ViewSubcategories,
            self::ViewWithdrawalSlips,
            self::CreateWithdrawalSlips,
            self::ViewJobOrders,
            self::CreateJobOrders,
            self::UpdateJobOrders,
            self::ViewProductClasses,
            self::ViewProductSubclasses,
            self::ViewJobOrderAgents,
            self::ViewCashReceipts,
            self::CreateCashReceipts,
            self::ViewPaymentOptions,
            self::ViewOwnTasks,
        ];
    }

    /**
     * Permissions granted to the manager role on top of the staff defaults.
     *
     * @return array<int, Permission>
     */
    public static function managerDefaults(): array
    {
        return [
            ...self::staffDefaults(),
            self::DeleteClients,
            self::ExportClients,
            self::DeleteClientContactPersons,
            self::CreateClientAgents,
            self::UpdateClientAgents,
            self::DeleteClientAgents,
            self::CreateEmployees,
            self::UpdateEmployees,
            self::DeleteEmployees,
            self::ManageEmployeeAccounts,
            self::CreateInventory,
            self::UpdateInventory,
            self::DeleteInventory,
            self::ImportInventory,
            self::ExportInventory,
            self::CreateCategories,
            self::UpdateCategories,
            self::DeleteCategories,
            self::CreateSubcategories,
            self::UpdateSubcategories,
            self::DeleteSubcategories,
            self::DeleteWithdrawalSlips,
            self::ArchiveJobOrders,
            self::RestoreJobOrders,
            self::ExportJobOrders,
            self::ImportJobOrders,
            self::CreateProductClasses,
            self::UpdateProductClasses,
            self::ArchiveProductClasses,
            self::RestoreProductClasses,
            self::CreateProductSubclasses,
            self::UpdateProductSubclasses,
            self::ArchiveProductSubclasses,
            self::RestoreProductSubclasses,
            self::CreateJobOrderAgents,
            self::UpdateJobOrderAgents,
            self::DeleteJobOrderAgents,
            self::UpdateCashReceipts,
            self::DeleteCashReceipts,
            self::ExportCashReceipts,
            self::CreatePaymentOptions,
            self::UpdatePaymentOptions,
            self::DeletePaymentOptions,
            self::ViewAccountsReceivable,
            self::ExportAccountsReceivable,
            self::ViewAllTasks,
        ];
    }
}
