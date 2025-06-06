// Whenever set new menu with icon, same manually import the icon name and set state property in `@commonServices/menuIcons` file.
export default [
    {
        icon: 'Home',
        title: 'Dashboard',
        route_name: 'admin.dashboard',
        subMenu: [
            {
                icon: 'Gauge',
                title: 'Orders',
                route_name: 'admin.dashboard',
                permission: 'dashboard_operational'
            },
            {
                icon: 'Gauge',
                title: 'Revenue',
                route_name: 'admin.revenue_view',
                permission: 'dashboard_store_revenue'
            },
            {
                icon: 'Gauge',
                title: 'Product',
                route_name: 'admin.store_revenue',
                permission: 'dashboard_store_revenue'
            },
            {
                icon: 'Gauge',
                title: 'Company',
                route_name: 'admin.business_view',
                permission: 'dashboard_business'
            },
            {
                icon: 'Gauge',
                title: 'Stock',
                route_name: 'admin.stock_overview',
                permission: 'dashboard_stock_overview'
            },
            {
                icon: 'Gauge',
                title: 'Performance',
                route_name: 'admin.sale_target',
                permission: 'dashboard_sale_target'
            },
            {
                icon: 'Gauge',
                title: 'Intelligence',
                route_name: 'admin.demand_forecasting',
            },
            {
                icon: 'Gauge',
                title: 'Season',
                route_name: 'admin.seasonal',
            },
            {
                icon: 'Gauge',
                title: 'Basket Analysis',
                route_name: 'admin.basket_analysis',
            },
            {
                icon: 'Gauge',
                title: 'Data Analysis',
                route_name: 'admin.data_analysis',
            },
            {
                icon: 'Gauge',
                title: 'Member',
                route_name: 'admin.member_dashboard_index',
            }
        ],
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
                        route_name: 'admin.products.index',
                        permission: 'product_read_record'
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Master Products',
                        route_name: 'admin.master_products.index',
                        permission: 'master_product_read_record'
                    },
                    {
                        icon: 'SquareArrowOutDownRight',
                        title: 'Supplier Catalog',
                        route_name: 'admin.external_products.index',
                        permission: 'product_read_record'
                    },
                    {
                        icon: 'Pencil',
                        title: 'Products Pending Approval',
                        route_name: 'admin.draft_products.index',
                        permission: 'product_read_record'
                    },
                    {
                        icon: 'LayoutList',
                        title: 'Product Collection',
                        route_name: 'admin.product_collections.index',
                        permission: 'product_collection_read_record'
                    },
                    {
                        icon: 'Network',
                        title: 'Categories',
                        route_name: 'admin.categories.index',
                        permission: 'category_read_record'
                    },
                    {
                        icon: 'QrCode',
                        title: 'Barcode',
                        route_name: 'admin.barcode_prints.index',
                        permission: 'barcode_read_record'
                    },
                ]
            },
            {
                icon: 'LayoutList',
                title: 'Stock Management',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'FileText',
                        title: 'Goods Received Notes',
                        route_name: 'admin.goods_received_notes.index',
                        permission: 'goods_received_note_read_record'
                    },
                    {
                        icon: 'Scale',
                        title: 'Stock Adjustments',
                        route_name: 'admin.stock_adjustments.index',
                        permission: 'stock_adjustment_read_record'
                    },
                    {
                        icon: 'Truck',
                        title: 'Stock Transfers',
                        route_name: 'admin.stock_transfers.index',
                        permission: 'stock_transfer_read_record',
                    },
                    {
                        icon: 'LayoutList',
                        title: 'Stock Transfer Reasons',
                        route_name: 'admin.stock_transfer_reasons.index',
                        permission: 'stock_transfer_reason_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Purchase Orders',
                        route_name: 'admin.purchase_orders.index',
                        permission: 'purchase_order_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Delivery Orders',
                        route_name: 'admin.purchase_order_fulfillments.delivery_orders',
                        permission: 'purchase_order_read_record',
                    },
                    {
                        icon: 'FileText',
                        title: 'Invoices',
                        route_name: 'admin.purchase_order_invoices.index',
                        permission: 'purchase_order_invoice_read_record',
                    },
                    {
                        icon: 'Package2',
                        title: 'Package Types',
                        route_name: 'admin.package_types.index',
                        permission: 'package_type_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Stock Positions',
                        route_name: 'admin.stock_positions.index',
                        permission: 'stock_position_read_record',
                    },
                    {
                        icon: 'ShoppingBasket',
                        title: 'Purchase Plans',
                        route_name: 'admin.purchase_plans.index',
                        permission: 'purchase_plan_read_record',
                    },
                ]
            },
            {
                icon: 'ShoppingBag',
                title: 'Manage Attributes',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'ShoppingBag',
                        title: 'Templates',
                        route_name: 'admin.templates.index',
                        permission: 'template_read_record'
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Template Attributes',
                        route_name: 'admin.template_attributes.index',
                        permission: 'template_attribute_read_record'
                    },
                    {
                        icon: 'Sun',
                        title: 'Seasons',
                        route_name: 'admin.seasons.index',
                        permission: 'season_read_record',
                    },
                    {
                        icon: 'Flame',
                        title: 'Styles',
                        route_name: 'admin.styles.index',
                        permission: 'style_read_record',
                    },
                    {
                        icon: 'Palette',
                        title: 'Colors',
                        route_name: 'admin.colors.index',
                        permission: 'color_read_record',
                    },
                    {
                        icon: 'Brush',
                        title: 'Color Groups',
                        route_name: 'admin.color_groups.index',
                        permission: 'color_group_read_record',
                    },
                    {
                        icon: 'Ruler',
                        title: 'Sizes',
                        route_name: 'admin.sizes.index',
                        permission: 'size_read_record'
                    },
                    {
                        icon: 'Scaling',
                        title: 'Size Groups',
                        route_name: 'admin.size_groups.index',
                        permission: 'size_group_read_record',
                    },
                    {
                        icon: 'Monitor',
                        title: 'Departments',
                        route_name: 'admin.departments.index',
                        permission: 'department_read_record',
                    },
                    {
                        icon: 'Tag',
                        title: 'Tags',
                        route_name: 'admin.tags.index',
                        permission: 'tag_read_record',
                    },
                    {
                        icon: 'Scale',
                        title: 'Unit of Measures (UOM)',
                        route_name: 'admin.unit_of_measures.index',
                        permission: 'unit_of_measure_read_record',
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
                        icon: 'Users',
                        title: 'Users',
                        route_name: 'admin.users.index',
                        permission: 'sale_return_reason_read_record',
                    },
                    {
                        title: 'Employee Management',
                        icon: 'Users',
                        route_name: '',
                        subSubSubMenu: [
                            {
                                icon: 'Users',
                                title: 'Employees',
                                route_name: 'admin.employees.index',
                                permission: 'employee_read_record',
                            },
                            {
                                icon: 'Users',
                                title: 'Employee Groups',
                                route_name: 'admin.employee_groups.index',
                                permission: 'employee_group_read_record'
                            },
                            {
                                icon: 'Briefcase',
                                title: 'Designations',
                                route_name: 'admin.designations.index',
                                permission: 'designation_read_record',
                            },
                        ]
                    },
                    {
                        title: 'Promoter Management',
                        icon: 'Users',
                        route_name: '',
                        subSubSubMenu: [
                            {
                                icon: 'Users',
                                title: 'Promoters',
                                route_name: 'admin.promoters.index',
                                permission: 'promoter_read_record',
                            },
                            {
                                icon: 'Users',
                                title: 'Promoter Groups',
                                route_name: 'admin.promoter_groups.index',
                                permission: 'promoter_group_read_record',
                            },
                        ]
                    },
                    {
                        title: 'Role Management',
                        icon: 'Users',
                        route_name: '',
                        subSubSubMenu: [
                            {
                                icon: 'Fingerprint',
                                title: 'Store Manager Permissions',
                                route_name: 'admin.store_manager_roles.index',
                                permission: 'store_manager_role_read_record',
                            },
                            {
                                icon: 'Fingerprint',
                                title: 'Warehouse Manager Permissions',
                                route_name: 'admin.warehouse_manager_roles.index',
                                permission: 'warehouse_manager_role_read_record',
                            },
                            {
                                icon: 'ShoppingCart',
                                title: 'Store Managers',
                                route_name: 'admin.store_managers.index',
                                permission: 'store_manager_read_record',
                            },
                            {
                                icon: 'UserPlus',
                                title: 'Warehouse Managers',
                                route_name: 'admin.warehouse_managers.index',
                                permission: 'warehouse_manager_read_record',
                            },
                            {
                                icon: 'User',
                                title: 'Directors',
                                route_name: 'admin.directors.index',
                                permission: 'director_read_record',
                            },
                        ]
                    },
                    {
                        title: 'Cashier Management',
                        icon: 'Users',
                        route_name: '',
                        subSubSubMenu: [
                            {
                                icon: 'Users',
                                title: 'Cashiers',
                                route_name: 'admin.cashiers.index',
                                permission: 'cashier_read_record',
                            },
                            {
                                icon: 'DollarSign',
                                title: 'Cashier Group',
                                route_name: 'admin.cashier_groups.index',
                                permission: 'cashier_group_read_record',
                            },
                        ]
                    },
                ]
            },
            {
                icon: 'DollarSign',
                title: 'Sales & Pricing',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'LayoutList',
                        title: 'Return Codes',
                        route_name: 'admin.sale_return_reasons.index',
                        permission: 'sale_return_reason_read_record',
                    },
                    {
                        icon: 'Wallet',
                        title: 'Payment Types',
                        route_name: 'admin.payment_types.index',
                        permission: 'payment_type_read_record',
                    },
                    {
                        icon: 'Slash',
                        title: 'Void Codes',
                        route_name: 'admin.void_sale_reasons.index',
                        permission: 'void_sale_reason_read_record',
                    },
                    {
                        icon: 'Coins',
                        title: 'Cash Flow Codes',
                        route_name: 'admin.cash_movement_reasons.index',
                        permission: 'cash_movement_reason_read_record',
                    },
                    {
                        icon: 'BarChart2',
                        title: 'Sell Through Ratio (STR)',
                        route_name: 'admin.sale_through_ratios.index',
                        permission: 'sale_through_ratio_read_record',
                    },
                    {
                        icon: 'Target',
                        title: 'Sales Target',
                        route_name: 'admin.sale_targets.index',
                        permission: 'sale_target_read_record',
                    },
                    {
                        icon: 'PackageCheck',
                        title: 'Configure Seasons',
                        route_name: 'admin.sale_seasons.index',
                        permission: 'sale_seasons_read_record',
                    },
                ]
            },
            {
                icon: 'Package',
                title: 'Company Structure',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Building',
                        title: 'Locations',
                        route_name: 'admin.locations.index',
                        permission: 'location_read_record',
                    },
                    {
                        icon: 'Laptop',
                        title: 'Counters',
                        route_name: 'admin.counters.index',
                        permission: 'counter_read_record',
                    },
                    {
                        icon: 'Factory',
                        title: 'Vendors',
                        route_name: 'admin.vendors.index',
                        permission: 'vendor_read_record',
                    },
                    {
                        icon: 'LocateFixed',
                        title: 'Regions',
                        route_name: 'admin.regions.index',
                        permission: 'region_read_record',
                    },
                    {
                        icon: 'Globe',
                        title: 'Countries',
                        route_name: 'admin.countries.index',
                        permission: 'country_read_record',
                    },
                    {
                        icon: 'Flag',
                        title: 'States',
                        route_name: 'admin.states.index',
                        permission: 'state_read_record',
                    },
                    {
                        icon: 'HousePlus',
                        title: 'Cities',
                        route_name: 'admin.cities.index',
                        permission: 'city_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Drivers',
                        route_name: 'admin.drivers.index',
                        permission: 'driver_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Vehicles',
                        route_name: 'admin.vehicles.index',
                        permission: 'vehicle_read_record',
                    },
                ]
            },
            {
                icon: 'Package',
                title: 'Configuration',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Megaphone',
                        title: 'POS Advertisement',
                        route_name: 'admin.pos_advertisements.index',
                        permission: 'pos_advertisement_read_record',
                    },
                    {
                        icon: 'LayoutGrid',
                        title: 'App Releases',
                        route_name: 'admin.pos_admin.index',
                        permission: 'app_release_read_record',
                    },
                    {
                        icon: 'Mail',
                        title: 'Email Recipient',
                        route_name: 'admin.email_recipients.index',
                        permission: 'email_recipient_read_record',
                    },
                    {
                        icon: 'Banknote',
                        title: 'Denominations',
                        route_name: 'admin.denominations.index',
                        permission: 'denomination_read_record',
                    },
                    {
                        icon: 'Download',
                        title: 'Import Records',
                        route_name: 'admin.import_records.index',
                        permission: 'import_record_read_record',
                    },
                    {
                        icon: 'Download',
                        title: 'Export Records',
                        route_name: 'admin.export_records.index',
                        permission: 'export_record_read_record',
                    },
                    {
                        icon: 'BellRing',
                        title: 'Automated Notifications',
                        route_name: 'admin.automated_notifications.index',
                        permission: 'automated_notification_read_record',
                    },
                    {
                        icon: 'BellPlus',
                        title: 'Manual Notifications',
                        route_name: 'admin.manual_notifications.index',
                        permission: 'manual_notification_read_record',
                    },
                    {
                        icon: 'Files',
                        title: 'Banner',
                        route_name: 'admin.banners.index',
                        permission: 'banner_read_record',
                    },
                    {
                        icon: 'DollarSign',
                        title: 'Online Sales Charges',
                        route_name: 'admin.online_sales_charges.index',
                        permission: 'online_sales_charges_read_record',
                    },
                    {
                        icon: 'Mails',
                        title: 'Mail Template',
                        route_name: 'admin.email_templates.index',
                        permission: 'email_template_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Shipping Zones',
                        route_name: 'admin.shipping_zones.index',
                        permission: 'shipping_zone_read_record',
                    },
                    {
                        icon: 'LayoutList',
                        title: 'Dynamic Menus',
                        route_name: 'admin.dynamic_menus.index',
                        permission: 'dynamic_menus_read_record',
                    },
                ]
            },
        ]
    },
    {
        icon: 'Contact',
        title: 'CRM',
        route_name: '',
        subMenu: [
            {
                icon: 'Users',
                title: 'Membership Management',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'UserCheck',
                        title: 'Configure Membership',
                        route_name: 'admin.memberships.index',
                        permission: 'membership_read_record',
                    },
                    {
                        icon: 'Group',
                        title: 'Member Groups',
                        route_name: 'admin.member_groups.index',
                        permission: 'member_group_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Registered Members',
                        route_name: 'admin.members.index',
                        permission: 'member_read_record'
                    },
                ],
            },
            {
                icon: 'Building',
                title: 'Campaign Management',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Building',
                        title: 'Loyalty Campaigns',
                        route_name: 'admin.loyalty_campaigns.index',
                        permission: 'loyalty_campaign_read_record',
                    },
                    {
                        icon: 'Building',
                        title: 'Configurations',
                        route_name: 'admin.loyalty_campaign_configurations.index',
                        permission: 'loyalty_campaign_configuration_read_record',
                    },
                    {
                        icon: 'Building',
                        title: 'Rewards',
                        route_name: 'admin.rewards.index',
                        permission: 'rewards_read_record',
                    },
                    {
                        icon: 'Activity',
                        title: 'CX Pulse',
                        route_name: 'cx_pulse',
                        permission: 'cx_pulse_read_record',
                    },
                ]
            }
        ]
    },
    {
        icon: 'Pocket',
        title: 'Offers & Discounts',
        route_name: '',
        subMenu: [
            {
                icon: 'Gift',
                title: 'Promotions',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Gift',
                        title: 'Configuration',
                        route_name: 'admin.promotions.index',
                        permission: 'promotion_read_record',
                    },
                    {
                        icon: 'Gift',
                        title: 'Mystery Gift',
                        route_name: 'admin.mystery_gifts.index',
                        permission: 'mystery_gift_read_record',
                    },
                    {
                        icon: 'Moon',
                        title: 'Price Markdown',
                        route_name: 'admin.dream_prices.index',
                        permission: 'dream_price_read_record',
                    },
                    {
                        icon: 'LayoutList',
                        title: 'Complimentary Setup',
                        route_name: 'admin.complimentary_item_reasons.index',
                        permission: 'complimentary_setup_read_record',
                    },
                    {
                        icon: 'Timer',
                        title: 'Happy Hours',
                        route_name: 'admin.happy_hours.index',
                        permission: 'happy_hour_read_record',
                    },
                ],
            },
            {
                icon: 'Notebook',
                title: 'Vouchers',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'Ticket',
                        title: 'Configuration',
                        route_name: 'admin.vouchers_configuration.index',
                        permission: 'vouchers_configuration_read_record',
                    },
                    {
                        icon: 'Banknote',
                        title: 'Cashback',
                        route_name: 'admin.cashbacks.index',
                        permission: 'cashback_read_record',
                    },
                    {
                        icon: 'Gift',
                        title: 'Gift Card',
                        route_name: 'admin.gift_cards.index',
                        permission: 'gift_card_read_record',
                    },
                ]
            },
        ]
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
                        route_name: 'admin.sales.index',
                        permission: 'sale_read_record',
                    },
                    {
                        icon: 'PackageSearch',
                        title: 'Return',
                        route_name: 'admin.sale_returns.index',
                        permission: 'sale_return_read_record',
                    },
                    {
                        icon: 'PackageSearch',
                        title: 'Return at Store B',
                        route_name: 'admin.different_store_returns.index',
                        permission: 'different_store_return_read_record',
                    },
                    {
                        icon: 'PackageMinus',
                        title: 'Layaway',
                        route_name: 'admin.layaway_sales.index',
                        permission: 'layaway_sale_read_record',
                    },
                    {
                        icon: 'PackageX',
                        title: 'Cancelled Layaway',
                        route_name: 'admin.cancel_layaway_sales.index',
                        permission: 'cancel_layaway_sale_read_record',
                    },
                    {
                        icon: 'PackageMinus',
                        title: 'Credit Sales',
                        route_name: 'admin.credit_sales.index',
                        permission: 'credit_sale_read_record',
                    },
                    {
                        icon: 'PackageX',
                        title: 'Void Sales',
                        route_name: 'admin.void_sales.index',
                        permission: 'void_sale_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Sales By Promoters',
                        route_name: 'admin.sales_by_promoters.index',
                        permission: 'sales_by_promoter_read_record',
                    },
                    {
                        icon: 'ArrowUpDown',
                        title: 'Exchange',
                        route_name: 'admin.sale_exchanges.index',
                        permission: 'sale_exchange_read_record',
                    },
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
                        route_name: 'admin.orders.b2bOrders',
                        permission: 'order_read_record',
                    },
                    {
                        icon: 'PackageCheck',
                        title: 'Return',
                        route_name: 'admin.order_returns.index',
                        permission: 'order_return_read_record',
                    },
                ]
            },
            {
                icon: 'ShoppingCart',
                title: 'Online Stores',
                route_name: '',
                subSubMenu: [
                    {
                        icon: 'PackageCheck',
                        title: 'Orders',
                        route_name: 'admin.orders.marketplaces_orders',
                        permission: 'order_read_record',
                    },
                    {
                        icon: 'PackageCheck',
                        title: 'Picking List',
                        route_name: 'admin.order_picking_lists.index',
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
                        route_name: 'admin.closed_counters.index',
                        permission: 'shift_close_read_record',

                    },
                    {
                        icon: 'BarChart2',
                        title: 'Day Close (Z Report)',
                        route_name: 'admin.day_close_report.index',
                        permission: 'day_close_read_record',
                    },
                    {
                        icon: 'Coins',
                        title: 'Cash Movements',
                        route_name: 'admin.cash_movements.index',
                        permission: 'cash_movement_read_record',
                    },
                    {
                        icon: 'Wallet',
                        title: 'Payment Type',
                        route_name: 'admin.payment_type_report.index',
                        permission: 'payment_type_report_read_record',
                    },
                    {
                        icon: 'DollarSign',
                        title: 'Commission',
                        route_name: 'admin.promoter_commission.index',
                        permission: 'commission_read_record',
                    },
                    {
                        icon: 'Trophy',
                        title: 'Sales Target',
                        route_name: 'admin.sale_achieved_targets.index',
                        permission: 'sale_achieved_target_read_record',
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
                        route_name: 'admin.member_sales_report.index',
                        permission: 'member_sale_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Employee sales',
                        route_name: 'admin.employee_sales_report.index',
                        permission: 'employee_sale_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Inventory',
                        route_name: 'admin.inventory_reports.index',
                        permission: 'inventory_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Supplier Inventory',
                        route_name: 'admin.external_inventory_reports.index',
                        permission: 'external_inventory_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Reserved Inventory',
                        route_name: 'admin.reserved_inventory_reports.index',
                        permission: 'reserved_inventory_read_record',
                    },
                    {
                        icon: 'Truck',
                        title: 'Transit Inventory',
                        route_name: 'admin.transit_inventory_reports.index',
                        permission: 'transit_inventory_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Products',
                        route_name: 'admin.products_report.index',
                        permission: 'product_report_read_record',
                    },
                    {
                        icon: 'ShoppingBasket',
                        title: ' Online Products',
                        route_name: 'admin.online_products_report.index',
                        permission: 'online_product_report_read_record',
                    },
                    {
                        icon: 'Database',
                        title: 'Consignment',
                        route_name: 'admin.consignment_report.index',
                        permission: 'consignment_report_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Profit Report',
                        route_name: 'admin.profits_and_losses_report.index',
                        permission: 'profit_and_loss_report_read_record',
                    },
                    {
                        icon: 'Layers',
                        title: 'Stock Movement Ledger',
                        route_name: 'admin.stock_movement_ledger_report.index',
                        permission: 'stock_movement_ledger_read_record',
                    },
                    {
                        icon: 'Tag',
                        title: 'Stock Movement Summary',
                        route_name: 'admin.stock_movement_summary_reports.index',
                        permission: 'stock_movement_summary_read_record',
                    },
                    {
                        icon: 'Coins',
                        title: 'Stock Take',
                        route_name: 'admin.stock_takes.index',
                        permission: 'stock_take_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Open Counters',
                        route_name: 'admin.open_counter_reports.index',
                        permission: 'open_counter_read_record',
                    },
                    {
                        icon: 'FileText',
                        title: 'Quantity Sold',
                        route_name: 'admin.quantity_sold_reports.index',
                        permission: 'quantity_sold_read_record',
                    },
                    {
                        icon: 'Tag',
                        title: 'Sell Through Ratio',
                        route_name: 'admin.sell_through_aggregate_reports.index',
                        permission: 'sell_through_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Inventory Aging',
                        route_name: 'admin.products_ageing_report.index',
                        permission: 'product_ageing_read_record',
                    },
                    {
                        icon: 'Activity',
                        title: 'ABC Analysis',
                        route_name: 'admin.sale_analysis_by_grade.index',
                        permission: 'sale_analysis_read_record',
                    },
                    {
                        icon: 'ShoppingBag',
                        title: 'Batch Expiry',
                        route_name: 'admin.batch_expiry.index',
                        permission: 'batch_expiry_read_record',
                    },
                    {
                        icon: 'Layers',
                        title: 'Product Serial Number',
                        route_name: 'admin.product_serial_number_report.index',
                        permission: 'product_serial_number_read_record',
                    },
                    {
                        icon: 'CheckCircle',
                        title: 'Genuine Product Verifications',
                        route_name: 'admin.product_verification_reports.index',
                        permission: 'genuine_product_verification_read_record',
                    },
                    {
                        icon: 'ReceiptText',
                        title: 'Genuine Receipt Verifications',
                        route_name: 'admin.receipt_verification_reports.index',
                        permission: 'genuine_receipt_verification_read_record',
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
                        route_name: 'admin.vouchers.index',
                        permission: 'voucher_read_record',
                    },
                    {
                        icon: 'Wallet',
                        title: 'Booking Payment',
                        route_name: 'admin.booking_payments.index',
                        permission: 'booking_payment_read_record',
                    },
                    {
                        icon: 'Users',
                        title: 'Members',
                        route_name: 'admin.members_report.index',
                        permission: 'member_report_read_record',
                    },
                    {
                        icon: 'Banknote',
                        title: 'Credit Notes',
                        route_name: 'admin.credit_notes.index',
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
                        route_name: 'admin.custom_reports.index',
                        permission: 'custom_report_read_record',
                    },
                    {
                        icon: 'Activity',
                        title: 'Activities',
                        route_name: 'admin.activities.index',
                        permission: 'activities_read_record',
                    },
                ]
            },
        ]
    },
    {
        icon: 'AirVent',
        title: 'External Login',
        route_name: 'admin.external_logins.index',
        permission: 'external_login_read_record',
    },
];
