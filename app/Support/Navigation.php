<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\User;
use App\Support\Navigation\NavigationGroup;
use App\Support\Navigation\NavigationItem;

/**
 * The application navigation.
 *
 * The navigation is declared in one place so the sidebar, the dashboard and
 * any future quick links stay in sync. Items are only rendered when their
 * route exists and when the signed in user holds the required permission.
 */
final class Navigation
{
    /**
     * The navigation groups.
     *
     * @return list<NavigationGroup>
     */
    public static function groups(): array
    {
        return [
            new NavigationGroup('Platform', [
                new NavigationItem('Dashboard', 'dashboard', 'home'),
                new NavigationItem('Calendar', 'calendar.all', 'calendar-days', Permission::ViewAllTasks),
                new NavigationItem('My Tasks', 'calendar.user', 'clipboard-document-check', Permission::ViewOwnTasks),
            ]),
            new NavigationGroup('Sales', [
                new NavigationItem('Clients', 'clients.index', 'users', Permission::ViewClients),
                new NavigationItem('Contract Persons', 'client-contact-persons.index', 'user-group', Permission::ViewClientContactPersons),
                new NavigationItem('Client Agents', 'client-agents.index', 'tag', Permission::ViewClientAgents),
                new NavigationItem('Job Orders', 'job-orders.index', 'clipboard-document-list', Permission::ViewJobOrders),
                new NavigationItem('Job Order Agents', 'job-order-agents.index', 'tag', Permission::ViewJobOrderAgents),
            ]),
            new NavigationGroup('Production', [
                new NavigationItem('Employees', 'employees.index', 'user-circle', Permission::ViewEmployees),
                new NavigationItem('Inventory', 'inventory.index', 'cube', Permission::ViewInventory),
                new NavigationItem('Categories', 'categories.index', 'folder', Permission::ViewCategories),
                new NavigationItem('Subcategories', 'subcategories.index', 'folder-open', Permission::ViewSubcategories),
                new NavigationItem('Classes', 'product-classes.index', 'square-3-stack-3d', Permission::ViewProductClasses),
                new NavigationItem('Subclasses', 'product-subclasses.index', 'square-2-stack', Permission::ViewProductSubclasses),
                new NavigationItem('Withdrawal Slips', 'withdrawal-slips.index', 'truck', Permission::ViewWithdrawalSlips),
            ]),
            new NavigationGroup('Finance', [
                new NavigationItem('Cash Receipts', 'cash-receipts.index', 'banknotes', Permission::ViewCashReceipts),
                new NavigationItem('Payment Options', 'payment-options.index', 'credit-card', Permission::ViewPaymentOptions),
                new NavigationItem('Accounts Receivable', 'reports.accounts-receivable', 'chart-bar', Permission::ViewAccountsReceivable),
            ]),
            new NavigationGroup('Administration', [
                new NavigationItem('User Accounts', 'admin.users.index', 'user-circle', Permission::ManageUserAccounts),
                new NavigationItem('Admin Tools', 'admin-tools.index', 'wrench-screwdriver', Permission::ExecuteAdminTools),
                new NavigationItem('Activity Log', 'activity-log.index', 'shield-check', Permission::ViewActivityLog),
            ]),
        ];
    }

    /**
     * The navigation groups that have at least one visible item.
     *
     * @return list<NavigationGroup>
     */
    public static function visibleGroups(?User $user): array
    {
        return array_values(array_filter(
            self::groups(),
            fn (NavigationGroup $group): bool => $group->visibleItems($user) !== [],
        ));
    }
}
