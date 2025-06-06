// Whenever set new menu with icon, same manually import the icon name and set state property in `@commonServices/menuIcons` file.
export default [
    {
        icon: 'Network',
        title: 'Super Admins',
        route_name: 'super_admin.super_admins.index',
    },
    {
        icon: 'Gem',
        title: 'Brands',
        route_name: 'super_admin.brands.index',
    },
    {
        icon: 'Building',
        title: 'Companies',
        route_name: 'super_admin.companies.index',
    },
    {
        icon: 'Users',
        title: 'Human Resources',
        route_name: '',
        subMenu: [
            {
                icon: 'Briefcase',
                title: 'Designations',
                route_name: 'super_admin.designations.index',
            },
            {
                icon: 'Users',
                title: 'Employees',
                route_name: 'super_admin.employees.index',
            },
            {
                icon: 'Users',
                title: 'Employee Groups',
                route_name: 'super_admin.employee_groups.index',
            },
        ]
    },
    {
        icon: 'Fingerprint',
        title: 'Roles Management',
        route_name: '',
        subMenu: [
            {
                icon: 'Users',
                title: 'Admins',
                route_name: 'super_admin.admins.index',
            },
            {
                icon: 'Fingerprint',
                title: 'Access Control (Roles)',
                route_name: 'super_admin.roles.index',
            },
        ]
    },
    {
        icon: 'Settings',
        title: 'System Configurations',
        route_name: '',
        subMenu: [
            {
                icon: 'Settings',
                title: 'Site Configurations',
                route_name: 'super_admin.site_configurations.index',
            },
            {
                icon: 'ServerCrash',
                title: 'External Connections',
                route_name: 'super_admin.external_connections.index',
            },
            {
                icon: 'Gem',
                title: 'Sales Channel',
                route_name: 'super_admin.sales_channel.index',
            },
            {
                icon: 'PackageOpen',
                title: 'Courier',
                route_name: 'super_admin.courier.index',
            },
            {
                icon: 'Blocks',
                title: 'Integration',
                route_name: 'super_admin.integrations.index',
            },
        ]
    },
];
