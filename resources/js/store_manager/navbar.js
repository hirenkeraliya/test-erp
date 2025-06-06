// Whenever set new menu with icon, same manually import the icon name and set state property in `@commonServices/menuIcons` file.
export default [
    {
        icon: 'Home',
        title: 'Dashboard',
        route_name: 'store_manager.dashboard',
    },
    {
        icon: 'Database',
        title: 'Inventory',
        route_name: '',
        subMenu: [
            {
                icon: 'ShoppingBag',
                title: 'Product Management',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'ShoppingBag',
                        title: 'Products',
                        route_name: 'store_manager.products.index',
                        permission: 'product_read_record'
                    },
                    {
                        icon: 'QrCode',
                        title: 'Barcode',
                        route_name: 'store_manager.barcode_prints.index',
                        permission: 'barcode_read_record'
                    }
                ]
            },
            {
                icon: 'LayoutList',
                title: 'Manage Inventory',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'FileText',
                        title: 'Goods Received Notes',
                        route_name: 'store_manager.goods_received_notes.index',
                        permission: 'goods_received_note_read_record'
                    },
                    {
                        icon: 'Scale',
                        title: 'Stock Adjustment',
                        route_name: 'store_manager.stock_adjustments.index',
                        permission: 'stock_adjustment_read_record'
                    },
                    {
                        icon: 'Truck',
                        title: 'Stock Transfer',
                        route_name: 'store_manager.stock_transfers.index',
                        permission: 'stock_transfer_read_record',
                    },
                    {
                        icon: 'Gauge',
                        title: 'Stock Transfers Overview',
                        route_name: 'store_manager.stock_transfers.overview',
                        permission: 'stock_transfer_overview_read_record',
                    },
                    {
                        icon: 'Coins',
                        title: 'Stock Takes',
                        route_name: 'store_manager.stock_takes.index',
                        permission: 'stock_take_read_record',

                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Purchase Orders',
                        route_name: 'store_manager.purchase_orders.index',
                        permission: 'purchase_order_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Delivery Orders',
                        route_name: 'store_manager.purchase_order_fulfillments.delivery_orders',
                    },
                    {
                        icon: 'FileText',
                        title: 'Invoices',
                        route_name: 'store_manager.purchase_order_invoices.index',
                        permission: 'purchase_order_invoice_read_record',
                    },
                ]
            },
        ]
    },
    {
        icon: 'Settings',
        title: 'Account Setup',
        route_name: '',
        subMenu: [
            {
                icon: 'Users',
                title: 'User Management',
                route_name: '',
                subSubMenu: [
                    {
                        title: 'Employees',
                        icon: 'Users',
                        route_name: 'store_manager.employees.index',
                        permission: 'employee_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Employee Groups',
                        route_name: 'store_manager.employee_groups.index',
                        permission: 'employee_group_read_record'
                    },
                    {
                        icon: 'Briefcase',
                        title: 'Designations',
                        route_name: 'store_manager.designations.index',
                        permission: 'designation_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Promoters',
                        route_name: 'store_manager.promoters.index',
                        permission: 'promoter_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Promoter Groups',
                        route_name: 'store_manager.promoter_groups.index',
                        permission: 'promoter_group_read_record',
                    },
                    {
                        icon: 'User',
                        title: 'Directors',
                        route_name: 'store_manager.directors.index',
                        permission: 'director_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Cashiers',
                        route_name: 'store_manager.cashiers.index',
                        permission: 'cashier_read_record',
                    },
                    {
                        icon: 'DollarSign',
                        title: 'Cashier Group',
                        route_name: 'store_manager.cashier_groups.index',
                        permission: 'cashier_group_read_record',
                    },
                ]
            },
            {
                icon: 'DollarSign',
                title: 'Sales & Pricing',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Target',
                        title: 'Sales Target',
                        route_name: 'store_manager.sale_targets.index',
                        permission: 'sale_target_read_record',
                    },
                ]
            },
        ]
    },
    {
        icon: 'BarChart2',
        title: 'Day Close',
        route_name: 'store_manager.day_close_counters.index',
    },
    {
        icon: 'Download',
        title: 'Import Record',
        route_name: 'store_manager.import_records.index',
    },
    {
        icon: 'Download',
        title: 'Export Record',
        route_name: 'store_manager.export_records.index',
    },
    {
        icon: 'LayoutGrid',
        title: 'App Realeases',
        route_name: 'store_manager.pos_admin.index',
    },

    {
        icon: 'Users',
        title: 'Member',
        route_name: 'store_manager.members.index',
        permission: 'member_read_record'
    },

    {
        icon: 'DollarSign',
        title: 'Order Management',
        route_name: '',
        subMenu: [
            {
                icon: 'ShoppingBag',
                title: 'Physical Locations',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'PackageCheck',
                        title: 'Sales',
                        route_name: 'store_manager.sales.index',
                        permission: 'sale_read_record',
                    },
                    {
                        icon: 'PackageSearch',
                        title: 'Return',
                        route_name: 'store_manager.sale_returns.index',
                        permission: 'sale_return_read_record',
                    },
                    {
                        icon: 'PackageSearch',
                        title: 'Different Locations Returns',
                        route_name: 'store_manager.different_store_returns.index',
                        permission: 'different_store_return_read_record',

                    },
                    {
                        icon: 'PackageMinus',
                        title: 'Layaway',
                        route_name: 'store_manager.layaway_sales.index',
                        permission: 'layaway_sale_read_record',
                    },
                    {
                        icon: 'PackageX',
                        title: 'Cancelled Layaway',
                        route_name: 'store_manager.cancel_layaway_sales.index',
                        permission: 'cancel_layaway_sale_read_record',
                    },
                    {
                        icon: 'PackageMinus',
                        title: 'Credit Sales',
                        route_name: 'store_manager.credit_sales.index',
                        permission: 'credit_sale_read_record',
                    },
                    {
                        icon: 'PackageX',
                        title: 'Void Sales',
                        route_name: 'store_manager.void_sales.index',
                        permission: 'void_sale_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Sales By Promoters',
                        route_name: 'store_manager.sales_by_promoters.index',
                        permission: 'sales_by_promoter_read_record',
                    },
                    {
                        icon: 'ArrowUpDown',
                        title: 'Exchange',
                        route_name: 'store_manager.sale_exchanges.index',
                        permission: 'sale_exchange_read_record',
                    }
                ]
            },
            {
                icon: 'ShoppingCart',
                title: 'B2B Orders',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'PackageCheck',
                        title: 'Orders',
                        route_name: 'store_manager.orders.b2bOrders',
                        permission: 'order_read_record',
                    },
                    {
                        icon: 'PackageCheck',
                        title: 'Order Return',
                        route_name: 'store_manager.order_returns.index',
                        permission: 'order_read_record'
                    },
                ]
            },
            {
                icon: 'ShoppingCart',
                title: 'Marketplaces',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'PackageCheck',
                        title: 'Orders',
                        route_name: 'store_manager.orders.marketplaces_orders',
                        permission: 'order_read_record',
                    },
                    {
                        icon: 'PackageCheck',
                        title: 'Picking List',
                        route_name: 'store_manager.order_picking_lists.index',
                        permission: 'order_picking_lists_read_record',
                    },
                ]
            },
        ]
    },

    {
        icon: 'FileBarChart',
        title: 'Reports',
        route_name: '',
        subMenu: [
            {
                icon: 'BarChart2',
                title: 'Financial Reports',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'ShieldClose',
                        title: 'Shift Close',
                        route_name: 'store_manager.closed_counters.index',
                        permission: 'shift_close_read_record',

                    },
                    {
                        icon: 'BarChart2',
                        title: 'Day Close (Z Report)',
                        route_name: 'store_manager.day_close_report.index',
                        permission: 'day_close_report_read_record',
                    },
                    {
                        icon: 'Coins',
                        title: 'Cash Movements',
                        route_name: 'store_manager.cash_movements.index',
                        permission: 'cash_movement_read_record',
                    },
                    {
                        icon: 'Wallet',
                        title: 'Payment Type',
                        route_name: 'store_manager.payment_type_report.index',
                        permission: 'payment_type_report_read_record',
                    },
                    {
                        icon: 'Trophy',
                        title: 'Sales Target',
                        route_name: 'store_manager.sale_achieved_targets.index',
                        permission: 'sale_achieved_target_read_record',
                    },
                    {
                        icon: 'DollarSign',
                        title: 'Commission',
                        route_name: 'store_manager.promoter_commission.index',
                        permission: 'commission_read_record',
                    },
                ]
            },

            {
                icon: 'Database',
                title: 'Sales and Inventory',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Users',
                        title: 'Member sales',
                        route_name: 'store_manager.member_sales_report.index',
                        permission: 'member_sale_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Employee sales',
                        route_name: 'store_manager.employee_sales_report.index',
                        permission: 'employee_sale_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Inventory',
                        route_name: 'store_manager.inventory_reports.index',
                        permission: 'inventory_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Supplier Inventory',
                        route_name: 'store_manager.external_inventory_reports.index',
                        permission: 'external_inventory_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Reserved Inventory',
                        route_name: 'store_manager.reserved_inventory_reports.index',
                        permission: 'reserved_inventory_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Transit Inventory',
                        route_name: 'store_manager.transit_inventory_reports.index',
                        permission: 'transit_inventory_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Products',
                        route_name: 'store_manager.products_report.index',
                        permission: 'product_report_read_record',
                    },
                    {
                        icon: 'ShoppingBasket',
                        title: 'Online Products',
                        route_name: 'store_manager.online_products_report.index',
                        permission: 'online_product_report_read_record',
                    },

                    {
                        icon: 'Layers',
                        title: 'Stock Movement Ledger',
                        route_name: 'store_manager.stock_movement_ledger_report.index',
                        permission: 'stock_movement_ledger_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Products Ageing',
                        route_name: 'store_manager.products_ageing_report.index',
                        permission: 'product_ageing_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Batch Expiry',
                        route_name: 'store_manager.batch_expiry.index',
                    },
                ]
            },

            {
                icon: 'Wallet',
                title: 'Members',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Ticket',
                        title: 'Voucher',
                        route_name: 'store_manager.vouchers.index',
                        permission: 'voucher_read_record',
                    },
                    {
                        icon: 'Wallet',
                        title: 'Booking Payment',
                        route_name: 'store_manager.booking_payments.index',
                        permission: 'booking_payment_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Members',
                        route_name: 'store_manager.members_report.index',
                        permission: 'member_report_read_record',
                    },
                    {
                        icon: 'Banknote',
                        title: 'Credit Notes',
                        route_name: 'store_manager.credit_notes.index',
                        permission: 'credit_note_read_record',
                    },
                ]
            },
            {
                icon: 'FileCog',
                title: 'Extra',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'FileCog',
                        title: 'Custom',
                        route_name: 'store_manager.custom_reports.index',
                        permission: 'custom_report_read_record',
                    },
                ]
            },

        ],
    },
];
