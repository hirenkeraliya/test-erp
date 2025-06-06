<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <style>
        #theme-color {
            background: {{ $themeColor }};
        }
    </style>
    @vite('resources/js/front/app.js')
</head>

<body>
    <div class="container mt-5 mx-auto" id="theme-color">
        <form action="{{ route('front.digital_invoice.digital_invoice_store', [$locationId, $counterId, $type, $offlineId]) }}" method="post">
            @csrf
            <div class="row mt-5">
                <div>
                    @if ($companyLogo)
                        <img alt="logo"
                            class="w-20 mx-auto rounded logo__image"
                            src="{{ $companyLogo }}"
                        >
                    @else
                        <p class="text-white">
                            {{ $companyName }}
                        </p>
                    @endif
                </div>
            </div>
            <div class="row p-4">
                <div class="col-sm-6 mb-3">
                    <label for="buyer_name" class="form-label text-white">Buyer Name</label>
                    <input type="text" name="buyer_name" id="buyer_name" class="form-control" value="{{ old('buyer_name') }}" required>
                    @error('buyer_name')
                        {{ $message}}
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="buyer_tin" class="form-label text-white">Buyer TIN</label>
                    <input type="text" name="buyer_tin" id="buyer_tin" class="form-control" value="{{ old('buyer_tin') }}" required>
                    @error('buyer_tin')
                        {{ $message}}
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="buyer_identification_number" class="form-label text-white">Buyer Identification Number</label>
                    <input type="text" name="buyer_identification_number" id="buyer_identification_number" class="form-control" value="{{ old('buyer_identification_number') }}" required>
                    @error('buyer_identification_number')
                        {{ $message}}
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="buyer_sst_number" class="form-label text-white">Buyer SST Number</label>
                    <input type="text" name="buyer_sst_number" id="buyer_sst_number" class="form-control" value="{{ old('buyer_sst_number') }}" required>
                    @error('buyer_sst_number')
                        {{ $message}}
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="buyer_email" class="form-label text-white">Buyer Email</label>
                    <input type="email" name="buyer_email" id="buyer_email" class="form-control" value="{{ old('buyer_email') }}">
                    @error('buyer_email')
                        {{ $message}}
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="buyer_address" class="form-label text-white">Buyer Address</label>
                    <textarea name="buyer_address" id="buyer_address" class="form-control" required>{{ old('buyer_address') }}</textarea>
                    @error('buyer_address')
                        {{ $message}}
                    @enderror
                </div>
                <div class="col-sm-6 mb-3">
                    <label for="buyer_contact" class="form-label text-white">Buyer Contact</label>
                    <input type="text" name="buyer_contact" id="buyer_contact" class="form-control" value="{{ old('buyer_contact') }}" required>
                    @error('buyer_contact')
                        {{ $message}}
                    @enderror
                </div>
            </div>
            <div class="row px-4 pb-4">
                <div class="d-flex">
                    <button type="submit" class="btn btn-outline-light">
                        Submit For E-Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
