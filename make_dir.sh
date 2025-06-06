DIRS_TO_CREATE=(
    "storage/app/public/product_image_zip"
    "storage/app/public/extract"
    "storage/app/barcode_print"
)

for x in "${DIRS_TO_CREATE[@]}"
do
:
    if [ ! -d "$x" ]; then
        mkdir "$x"
        echo "$x was created"
    fi
done
