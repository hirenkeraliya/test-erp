<div>
    @if (!empty($filterData['location_type']))
    <p>
        Location Type: <strong>{{ $filterData['location_type'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['compare_location_type']))
    <p>
        Compare Location Type: <strong>{{ $filterData['compare_location_type'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['location_name']))
    <p>
        Location Name: <strong>{{ $filterData['location_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['compare_location_name']))
    <p>
        Compare Location Name: <strong>{{ $filterData['compare_location_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['report_type']))
    <p>
        Report Type: <strong>{{ $filterData['report_type'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['product_name']))
    <p>
        Product Name: <strong>{{ $filterData['product_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['promoter_name']))
    <p>
        Promoter Name: <strong>{{ $filterData['promoter_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['promoter_group_name']))
    <p>
        Promoter Group Name: <strong>{{ $filterData['promoter_group_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['product_collection']))
    <p>
        Product Collection: <strong>{{ $filterData['product_collection'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['category_name']))
    <p>
        Category Name: <strong>{{ $filterData['category_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['brand']))
    <p>
        Brands: <strong>{{ $filterData['brand'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['size']))
    <p>
        Sizes: <strong>{{ $filterData['size'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['color']))
    <p>
        Color: <strong>{{ $filterData['color'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['department']))
    <p>
        Department: <strong>{{ $filterData['department'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['article_number']))
    <p>
        Article Number: <strong>{{ $filterData['article_number'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['tags']))
    <p>
        Tags: <strong>{{ $filterData['tags'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['age_of_product_type']))
    <p>
        Age Of Product Type: <strong>{{ $filterData['age_of_product_type'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['age_category']))
    <p>
        Age Category: <strong>{{ $filterData['age_category'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['last_selling_date_range']))
    <p>
        Last Selling Date Range: <strong>{{ $filterData['last_selling_date_range'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['counters']))
    <p>
        Counters: <strong>{{ $filterData['counters'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['regions']))
    <p>
        Regions: <strong>{{ $filterData['regions'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['compare_regions']))
    <p>
        Compare Regions: <strong>{{ $filterData['compare_regions'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['employee_name']))
    <p>
        Employee Name: <strong>{{ $filterData['employee_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['module_name']))
    <p>
        Module Name: <strong>{{ $filterData['module_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['style']))
    <p>
        Style: <strong>{{ $filterData['style'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['grade_filters']))
    <p>
        Grade Filters: <strong>{{ $filterData['grade_filters'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['date']))
    <p>
        Date: <strong>{{ $filterData['date'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['order_type']))
    <p>
        Order Type: <strong>{{ $filterData['order_type'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['member_name']))
    <p>
        Member Name: <strong>{{ $filterData['member_name'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['e_invoice_submitted']))
    <p>
        E Invoice Submitted: <strong>{{ $filterData['e_invoice_submitted'] }}</strong>
    </p>
    @endif

    @if (!empty($filterData['date_range']))
    <p>
        Date Range: <strong>{{ $filterData['date_range'] }}</strong>
    </p>
    @endif
</div>
