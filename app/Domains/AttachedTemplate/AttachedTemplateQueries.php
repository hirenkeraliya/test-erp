<?php

declare(strict_types=1);

namespace App\Domains\AttachedTemplate;

use App\Models\AttachedTemplate;
use App\Models\MasterProduct;
use App\Models\Product;

class AttachedTemplateQueries
{
    public function getBasicColumnNames(): string
    {
        return 'id,model_id,template_id';
    }

    public function addNew(array $attachedTemplateRecord): AttachedTemplate
    {
        return AttachedTemplate::create($attachedTemplateRecord);
    }

    public function delete(Product $product): void
    {
        foreach ($product->attachedTemplates as $template) {
            $template->delete();
        }
    }

    public function deleteMasterProduct(MasterProduct $masterProduct): void
    {
        foreach ($masterProduct->attachedTemplates as $template) {
            $template->delete();
        }
    }
}
