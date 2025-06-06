// Whenever set new menu with icon, same manually import the icon name and set state property in `@commonServices/menuIcons` file.
export default [
    {
        icon: 'Home',
        title: 'Dashboard',
        route_name: 'warehouse_manager.stock_overview',
    },
    {
        icon: 'Database',
        title: 'Inventory',
        route_name: '',
        subMenu: [
            {
                icon: 'ShoppingBag',
                title: 'Manage Products',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'ShoppingBag',
                        title: 'Products',
                        route_name: 'warehouse_manager.products.index',
                        permission: 'product_read_record'
                    },
                    {
                        icon: 'QrCode',
                        title: 'Barcode',
                        route_name: 'warehouse_manager.barcode_prints.index',
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
                        route_name: 'warehouse_manager.goods_received_notes.index',
                        permission: 'goods_received_note_read_record'
                    },
                    {
                        icon: 'Scale',
                        title: 'Stock Adjustments',
                        route_name: 'warehouse_manager.stock_adjustments.index',
                        permission: 'stock_adjustment_read_record'
                    },
                    {
                        icon: 'Truck',
                        title: 'Stock Transfer',
                        route_name: 'warehouse_manager.stock_transfers.index',
                        permission: 'stock_transfer_read_record',
                    },
                    {
                        icon: 'Gauge',
                        title: 'Stock Transfers Overview',
                        route_name: 'warehouse_manager.stock_transfers.overview',
                    },
                    {
                        icon: 'Coins',
                        title: 'Stock Takes',
                        route_name: 'warehouse_manager.stock_takes.index',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Purchase Orders',
                        route_name: 'warehouse_manager.purchase_orders.index',
                        permission: 'purchase_order_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Delivery Orders',
                        route_name: 'warehouse_manager.purchase_order_fulfillments.delivery_orders',
                    },
                    {
                        icon: 'FileText',
                        title: 'Invoices',
                        route_name: 'warehouse_manager.purchase_order_invoices.index',
                        permission: 'purchase_order_invoice_read_record',
                    },
                ]
            }
        ]
    },
    {
        icon: 'Download',
        title: 'Import Record',
        route_name: 'warehouse_manager.import_records.index',
        permission: 'import_record_read_record',
    },
    {
        icon: 'Download',
        title: 'Export Record',
        route_name: 'warehouse_manager.export_records.index',
        permission: 'export_record_read_record',
    },
    {
        icon: 'FileBarChart',
        title: 'Reports',
        route_name: '',
        subMenu: [
            {
                icon: 'Database',
                title: 'Sales and Inventory',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Database',
                        title: 'Inventory',
                        route_name: 'warehouse_manager.inventory_reports.index',
                        permission: 'inventory_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Supplier Inventory',
                        route_name: 'warehouse_manager.external_inventory_reports.index',
                        permission: 'external_inventory_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Reserved Inventory',
                        route_name: 'warehouse_manager.reserved_inventory_reports.index',
                        permission: 'reserved_inventory_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Transit Inventory',
                        route_name: 'warehouse_manager.transit_inventory_reports.index',
                        permission: 'transit_inventory_read_record',
                    },
                    {
                        icon: 'Layers',
                        title: 'Stock Movement Ledger',
                        route_name: 'warehouse_manager.stock_movement_ledger_report.index',
                        permission: 'stock_movement_ledger_read_record',
                    },

                    {
                        icon: 'ShoppingBag',
                        title: 'Batch Expiry',
                        route_name: 'warehouse_manager.batch_expiry.index',
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
                        route_name: 'warehouse_manager.custom_reports.index',
                        permission: 'custom_report_read_record',
                    },
                ]
            },
        ],
    },
    {
        icon: 'AirVent',
        title: 'External Login',
        route_name: 'warehouse_manager.external_logins.index',
    },
];
