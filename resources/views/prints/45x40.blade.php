<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Print</title>
    <style>
        @page {
            margin: 0;
        }
        .sticker-width {
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .sticker-layout-45-x-40 {
            display: inline-block;
            width: 48%;
            padding-left: 1.4%;
            vertical-align: top;
        }
        .fs-5 {
            font-size: 0.7rem !important;
        }
        .text-center {
            text-align: center;
        }
        .row-fixed-height-45-x-40-2-sticker {
            height: 12px;
        }
        .two-line-box-45-x-40 {
            display: -webkit-box;
            overflow: hidden;
            height: 23px;
        }
        .row {
            display: flex;
        }
        .py-03 {
            padding-top: 0.03rem !important;
            padding-bottom: 0.03rem !important;
        }
        .one-line-box {
            display: -webkit-box;
            overflow: hidden;
        }
        .barcode-image {
            width: 90%;
            margin-top: 1px;
            margin-bottom: 2px;
        }
        .fs-6 {
            font-size: 0.5rem !important;
        }
        .fs-7 {
            font-size: 0.8rem !important;
        }
        .page-break {
            page-break-after: always;
        }
        .row-for-variant {
            display: flex;
            white-space: normal;
            word-wrap: break-word;
        }
        .multiple-line-box {
            padding-top: 10px;
            line-height: 1;
            max-height: none;
        }
    </style>
</head>
<body>
    @foreach($products as $product)
        @php
            $counter = 0;
        @endphp
        <div class="sticker-width">
            @for($i = 1; $i <= $product['print_quantity']; $i++)
                @if($counter % 2 == 0)

                    @if($counter > 0)
                        <div class="page-break"></div>
                        </div>
                    @endif
                    <div class="row">
                @endif
                <div class="sticker-layout-45-x-40">
                    <div class="fs-5 text-center row-fixed-height-45-x-40-2-sticker">
                        <strong>{{ $product['brand_name'] ?? '' }}</strong>
                    </div>

                    <div class="fs-5 two-line-box-45-x-40">
                        <strong>{{ $product->name }}</strong>
                    </div>

                    <div class="row py-03 row-fixed-height-45-x-40-2-sticker">
                        <div class="fs-5 one-line-box">
                            <strong>
                                Art.No:
                                {{ $product['article_number'] ?? '' }}
                            </strong>
                        </div>
                    </div>

                    @if(! $product_variant)
                    <div class="row py-03 row-fixed-height-45-x-40-2-sticker">
                        <div class="fs-5 one-line-box">
                            <strong>
                                Color:
                                {{ $product['color_name'] ?? '' }}
                            </strong>
                        </div>
                    </div>
                    @endif

                    @if(! $product_variant)
                    <div class="row py-03 row-fixed-height-45-x-40-2-sticker">
                        <div class="fs-5 one-line-box">
                            <strong>
                                Size:
                                {{ $product['size_name'] ?? ''}}
                            </strong>
                        </div>
                    </div>
                    @endif

                    @if(! $product_variant)
                    <div class="row py-03 row-fixed-height-45-x-40-2-sticker">
                        <div class="fs-5 one-line-box">
                            <strong>
                                Style:
                                {{ $product['style_name'] ?? ''}}
                            </strong>
                        </div>
                    </div>
                    @endif

                    @if($product_variant)
                    <div class="row py-03 row-for-variant">
                        <div class="fs-5 multiple-line-box">
                            <strong>
                                @foreach ($product['product_variant_values'] as $productVariantValue)
                                    {{ $productVariantValue }}
                                @endforeach
                            </strong>
                        </div>
                    </div>
                    @endif

                    <img src="data:image/png;base64,{{ $product['barcode'] }}" height="25" class="barcode-image">

                    <div class="text-center">
                        <div class="fs-6">{{ $product->upc }}</div>
                        <div class="fs-7"><strong>{{ $product['price'] }}</strong></div>

                        @isset($remark)
                            <div class="fs-6">{{ $remark }}</div>
                        @endisset
                    </div>
                </div>
                @php
                    $counter++;
                @endphp
            @endFor

            @if($counter % 2 != 0)
                </div>
            @endif
        </div>
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
