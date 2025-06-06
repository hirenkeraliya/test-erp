<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Member Details</title>

    <link rel="stylesheet" href="{{ asset('/css/report-pdf.css') }}">
</head>

<body class="arial-font-custom-report">
    <h4>
        {{ $company->name }} ( {{ $company->code }} )
    </h4>

    <div class="date-display">
        <h4>
            Member Details
        </h4>

        <p>
            Date: {{ $date }}
        </p>
    </div>

    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center">
                        Title
                    </th>

                    <th class="text-center">
                        Race
                    </th>

                    <th class="text-center">
                        First Name
                    </th>

                    <th class="text-center">
                        Last Name
                    </th>

                    <th class="text-center">
                        Gender
                    </th>

                    <th class="text-center">
                        Date Of Birth
                    </th>

                    <th class="text-center">
                        Email
                    </th>

                    <th class="text-center">
                        Mobile Number
                    </th>

                    <th class="text-center">
                        Card Number
                    </th>

                    <th class="text-center">
                        Type
                    </th>

                    <th class="text-center">
                        Loyalty Points
                    </th>

                    <th class="text-center">
                        Company Name
                    </th>

                    <th class="text-center">
                        Company Registration Number
                    </th>

                    <th class="text-center">
                        Company Tax Number
                    </th>

                    <th class="text-center">
                        Company Address
                    </th>

                    <th class="text-center">
                        Company Phone
                    </th>

                    <th class="text-center">
                        Created Location
                    </th>

                    <th class="text-center">
                        Notes
                    </th>

                    <th class="text-center">
                        Loyalty Points
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($memberDetails as $member)
                    <tr class="page-break-inside-avoid">
                        <td>{{ $member['title'] }}</td>
                        <td>{{ $member['race'] }} </td>
                        <td>{{ $member['first_name'] }} </td>
                        <td>{{ $member['last_name'] }} </td>
                        <td>{{ $member['gender'] }} </td>
                        <td>{{ $member['date_of_birth'] }} </td>
                        <td>{{ $member['email'] }}</td>
                        <td>{{ $member['mobile_number'] }}</td>
                        <td>{{ $member['card_number'] }}</td>
                        <td>{{ $member['type'] }}</td>
                        <td class="text-center">{{ $member['loyalty_points'] }}</td>
                        <td>{{ $member['company_name'] }}</td>
                        <td>{{ $member['company_registration_number'] }}</td>
                        <td>{{ $member['company_tax_number'] }}</td>
                        <td>{{ $member['company_address'] }}</td>
                        <td>{{ $member['company_phone'] }}</td>
                        <td>{{ $member['created_location'] }}</td>
                        <td>{{ $member['notes'] }}</td>
                        <td>{{ $member['loyalty_points'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center"> No Record Found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>