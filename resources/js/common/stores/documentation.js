export const getProductCollectionHelpText = () => {
    return `The Product Collection Module is designed to efficiently manage and organize active products into specific collections based on various configurations, filters, and selections.</br></br>

            <strong>Note:</strong> This module is act based on company level <strong> Auto Include Collection</strong> configuration.</br>
            When <strong>Auto Include Collection</strong> is OFF then the <strong>Sync</strong> button is provided on the product collections page which has to be manually synced by clicking but, when <strong>Auto Include Collection</strong> is ON, product collection will be automatically synced.</br></br>

            Here’s a brief overview of how the module functions:</br>

            <ul class='list-decimal pl-4 mt-2'>
                <li class='text-justify mb-2'>
                    <strong>Dynamic Updates:</strong> The module can dynamically update the collections as product(add/update/import) data changes or as new/update configurations and filters are applied, ensuring that the collections remain relevant and up-to-date.
                </li>

                <li class='text-justify mb-2'>
                    <strong>Scanning Products:</strong> The module scans the entire products based on the defined configurations and filters. This ensures that all relevant products are considered for inclusion in the collections.
                </li>

                <li class='text-justify mb-2'>
                    <strong>Storing Collections:</strong> Whenever perform the Draft/Supplier catalog products approved, merge, archived and restore the product, attach with that collections and up-to-date.
                </li>
            </ul>`;
};

export const getDraftProductHelpText = () => {
    return `The Draft Products Module allows admin to create and manage products in a draft state before they are approved and published to the products list. This process ensures that all products meet the required standards and approvals before becoming available in the system.</br></br>

            Here’s a brief overview of how the module functions:</br>

            <ul class='list-decimal pl-4 mt-2'>
                <li class='text-justify mb-2'>
                    <strong>Draft Creation:</strong> Admins can create new products, save them as drafts, view product details, delete, and edit the products.
                </li>

                <li class='text-justify mb-2'>
                    <strong>How Approvals Work:</strong></br>
                    <ul class='list-disc pl-4 mt-2'>
                        <li>
                            <strong>Creators cannot approve their own products:</strong> This is the default setting at company level. If a creator creates a draft product, it will need to be reviewed and approved by someone else, typically an administrator, before it becomes active.
                        </li>
                        <li>
                            <strong>Creators can approve their own products:</strong>  If this option is enabled at company level, creators will have the ability to review and approve their own draft products.
                        </li>
                    </ul>

                </li>

                <li class='text-justify mb-2'>
                    <strong>Similar Product:</strong> If a draft product matches any active, archived, and other draft product in the system, indicate this with a red marker. A match means that some column values of the draft product are identical to those of an active, archived, and other draft product. Additionally, display a list of similar products.
                </li>
            </ul>

            <strong class='text-blue-500'>These are the column names we matched on.</strong></br>

            <div class='flex justify-around'>
                <div>
                    <ul class='list-decimal mt-2'>
                        <li>Categories</li>
                        <li>Unit Of Measure</li>
                        <li>Season</li>
                        <li>Department</li>
                        <li>Sub Department</li>
                        <li>Color</li>
                        <li>Size</li>
                        <li>Brand</li>
                        <li>Style</li>
                        <li>Ean</li>
                        <li>Custom Sku</li>
                        <li>Manufacturer Sku</li>
                        <li>Article Number</li>
                        <li>Type</li>
                        <li>Retail Price</li>
                        <li>Franchise Price 1</li>
                        <li>Franchise Price 2</li>
                    </ul>
                </div>
                <div>
                    <ul class='list-decimal mt-2'>
                        <li value='18'>Franchise Price 3</li>
                        <li>Wholesale Price</li>
                        <li>Company Or Tender Price</li>
                        <li>Branch Price</li>
                        <li>Minimum Price</li>
                        <li>Original Capital Price</li>
                        <li>Capital Price</li>
                        <li>Staff Price</li>
                        <li>Purchase Cost</li>
                        <li>Is Temporarily Unavailable</li>
                        <li>Has Batch</li>
                        <li>Is Non Inventory</li>
                        <li>Is Non Selling Item</li>
                        <li>Is Available In Pos</li>
                        <li>Is Available In Ecommerce</li>
                        <li>Online Price</li>
                    </ul>
                </div>
            </div>
            `;
};

export const autoMatedNotificationHelpText = () => {
    return `The Automated Notifications feature enables users to configure automated alerts within the system. Users can specify the type of notification, set a timeframe for the notifications, and optionally designate email recipients.</br></br>

            Here’s a brief overview of how the module functions:</br>

            <ul class='list-decimal pl-4 mt-2'>
                <li class='text-justify mb-2'>
                    <strong>Type :</strong> User can select various type of notification which will show you remaining type(s) only(selected will be disappear).
                </li>

                <li class='text-justify mb-2'>
                    <strong>Timeframe :</strong>  Users select either the "Limit By Day Of Month" or "Limit By Day Of Week" option.
                </li>

                <li class='text-justify mb-2'>
                    <strong>Automated Email Recipients :</strong> Users can multi select email recipients, If email recipients are specified, the system will send email only.
                </li>
            </ul>`;
};

export const ActivitiesReportHelpText = () => {
    return 'The Activities Report provides advanced filters and search options, enabling detailed analysis across both base and child modules. It also offers seamless export capabilities.';
};

export const MemberDetailsHelpText = () => {
    return 'Member Details section includes Profile Details, Membership Details, Preferences, Other Information, and Purchase and Return History. The Purchase History displays regular, completed credit and completed layaway sales.';
};

export const BrandHelpText = () => {
    return `The Brands module is designed to manage and associate brands within the application. This module includes the following key features:<br><br>

            <ul class='list-decimal pl-4 mt-2'>
                <li class='text-justify mb-2'>
                    <strong>Company Association :</strong> Select the brands at company level for further operations.
                </li>

                <li class='text-justify mb-2'>
                    <strong>Products Module: :</strong>  Brands are linked to products under the respective company, enabling brand-specific product management.
                </li>

                <li class='text-justify mb-2'>
                    <strong>Locations Module: :</strong>  Brands are also attached to locations, facilitating brand tracking and management within store operations.
                </li>

                <li class='text-justify mb-2'>
                    <strong>Promotions & Reports :</strong> The brands integrated into the company structure, products, and locations are further utilized in generating reports/charts and creating promotional activities. This ensures that brand-related data is consistently available across various functionalities within the system.
                </li>
            </ul>`;
};

export const CompanyHelpText = () => {
    return `The Company module serves as the foundation for managing company-specific data and organizing various business entities and modules under a unified structure. Here centrally configure some settings/formats, enable/disable some features to apply.<br>
        Please hover the add/edit form input-box info icon to know more.<br><br>`;
};

export const DesignationHelpText = () => {
    return `The Designation module is designed to manage employee roles and titles within the organization. This module allows for the structured organization of job positions and their associated responsibilities.<br>`;
};

export const EmployeeHelpText = () => {
    return `The Employee module is designed to manage the workforce within the organization, providing comprehensive functionality for handling employee-related data and processes.<br>`;
};

export const EmployeeGroupHelpText = () => {
    return `The Employee Group module is designed to categorize employees into groups, allowing for the application of specific settings and permissions that are consistent across the group. This module is particularly useful for managing purchase-related restrictions and other group-based functionalities. Key features include:<br>

        <ul class='list-decimal pl-4 mt-2'>
            <li class='text-justify mb-2'>
                <strong>Purchase Limit Type :</strong> Defines the type of purchase limit applied to the group (e.g., by items, by sales, by amounts).
            </li>

            <li class='text-justify mb-2'>
                <strong>Limit Reset Type :</strong>  Specifies how and when the purchase limit resets (e.g., daily, weekly, monthly).
            </li>

            <li class='text-justify mb-2'>
                <strong>Add/Edit Employee Process :</strong> During the employee add/edit process, the employee can be assigned to an employee group, thereby inheriting the purchase limit type and reset type defined for that group.
            </li>
        </ul>
    `;
};

export const AdminHelpText = () => {
    return `The Admin module is designed to manage administrative users within the system, providing the necessary tools to control access to various modules, reports, and other key functionalities. This module includes features for creating and managing admin users, assigning roles, and controlling access permissions.<br>`;
};

export const RoleHelpText = () => {
    return `The Role module is designed to define and manage roles within the system, allowing for precise control over what actions admin users can perform in various modules. This module enables the creation of roles with specific permissions, which can be assigned to admin users during the add/edit process in the Admin module.<br>`;
};

export const TransitInventoryReportHelpText = () => {
    return `<strong>Transit Inventory report for display the in-transit stock(s) until the destination location received it.</strong><br>

        Here are the below functionality in different types with statuses:<br>

        <ul class='list-decimal pl-4 mt-2'>
            <li class='text-justify mb-2'>
                <strong>Transfer Order :</strong> <br>
                <ul class='list-disc pl-4 mt-2'>
                    <li class='text-justify mb-2'>
                        When mark as open, inventory will be released from the reserved stock table and moved to transit inventory until the closed.
                    </li>
                    <li class='text-justify mb-2'>
                        When mark as closed(normal), inventory will be released from the transit inventory.
                    </li>
                    <li class='text-justify mb-2'>
                        When mark as closed with any discrepancy, inventory will be released from the transit inventory with actual/original quantity.
                    </li>
                    <li class='text-justify mb-2'>
                        When mark as cancelled after shipped/transit, inventory will be released from the transit inventory.
                    </li>
                    <li class='text-justify mb-2'>
                        When mark as rejected after open, inventory will be released from the transit inventory.
                    </li>
                </ul>
            </li>

            <br>

            <li class='text-justify mb-2'>
                <strong>Request Order :</strong> <br>
                <ul class='list-disc pl-4 mt-2'>
                    <li class='text-justify mb-2'>
                        When mark as shipped, inventory will be released from the reserved stock table and moved to transit inventory until the closed.
                    </li>
                    <li class='text-justify mb-2'>
                        When mark as closed(normal), inventory will be released from the transit inventory.
                    </li>
                    <li class='text-justify mb-2'>
                        When mark as closed with any discrepancy, inventory will be released from the transit inventory with actual/original quantity.
                    </li>
                    <li class='text-justify mb-2'>
                        When mark as cancelled after shipped/transit, inventory will be released from the transit inventory.
                    </li>
                </ul>
            </li>
        </ul>
    `;
};
