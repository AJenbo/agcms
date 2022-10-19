<?php

namespace App\Http\Controllers\Admin;

use App\Models\InterfaceRichText;
use App\Models\Page;
use App\Services\DbService;
use App\Services\OrmService;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends AbstractAdminController
{
    /** @var array<int, string> */
    private array $header = [
        'ID',
        'Type',
        'SKU',
        'Name',
        'Published',
        'Is featured?',
        'Visibility in catalog',
        'Short description',
        'Description',
        'Date sale price starts',
        'Date sale price ends',
        'Tax status',
        'Tax class',
        'In stock?',
        'Stock',
        'Backorders allowed?',
        'Sold individually?',
        'Weight (lbs)',
        'Length (in)',
        'Width (in)',
        'Height (in)',
        'Allow customer reviews?',
        'Purchase note',
        'Sale price',
        'Regular price',
        'Categories',
        'Tags',
        'Shipping class',
        'Images',
        'Download limit',
        'Download expiry days',
        'Parent',
        'Grouped products',
        'Upsells',
        'Cross-sells',
        'External URL',
        'Button text',
        'Position',
    ];

    public function index(Request $request): Response
    {
        app(DbService::class)->addLoadedTable('bind', 'kat', 'krav', 'maerke', 'sider');
        $response = $this->cachedResponse();
        if ($response->isNotModified($request)) {
            return $response;
        }

        $pages = app(OrmService::class)->getByQuery(Page::class, 'SELECT * FROM sider');

        $maxAttributes = 0;
        foreach ($pages as $page) {
            $columns = [];
            foreach ($page->getTables() as $table) {
                if (!$table->getRows()) {
                    continue;
                }
                foreach ($table->getColumns() as $column) {
                    if ($column['type'] <= 1) {
                        $columns[$column['title']] = true;
                    }
                }
            }
            $maxAttributes = max(count($columns), $maxAttributes);
        }

        for ($i = 1; $i <= $maxAttributes; $i++) {
            $this->header[] = 'Attribute ' . $i . ' name';
            $this->header[] = 'Attribute ' . $i . ' value(s)';
            $this->header[] = 'Attribute ' . $i . ' visible';
            $this->header[] = 'Attribute ' . $i . ' global';
        }
        $data = [$this->header];

        foreach ($pages as $page) {
            $tables = $page->getTables();
            $type = $tables ? 'variable' : 'simple';
            $productData = $this->getPageData($type, $page);
            $variations = [];
            $attributeValues = [];
            $variationId = 0;
            foreach ($tables as $table) {
                $rows = $table->getRows();
                if (!$rows) {
                    continue;
                }
                $columns = $table->getColumns();
                foreach ($columns as $column) {
                    if ($column['type'] <= 1 && !isset($attributeValues[$column['title']])) {
                        $attributeValues[$column['title']] = [];
                    }
                }
                foreach ($rows as $row) {
                    if ($table->hasLinks() && $row['page']) {
                        continue;
                    }

                    $rowData = $productData;

                    $rowData[0] .= '-' . $variationId;
                    $rowData[1] = 'variation';
                    $rowData[2] .= '-' . $variationId;
                    $rowData[31] = 'id:' . $productData[0];

                    $price = null;
                    $oldPrice = null;
                    $salePrice = null;
                    foreach ($columns as $i => $column) {
                        if ($column['type'] === 2) {
                            $price = $row[$i];

                            continue;
                        }
                        if ($column['type'] === 3) {
                            $salePrice = $row[$i];

                            continue;
                        }
                        if ($column['type'] === 4) {
                            $oldPrice = $row[$i];

                            continue;
                        }
                        $rowData[] = $column['title'];
                        $rowData[] = $row[$i];
                        $rowData[] = '';
                        $rowData[] = '0';

                        $attributeValues[$column['title']][] = $row[$i];
                    }

                    if (!$salePrice && $oldPrice && $price) {
                        $salePrice = $price;
                    }
                    if ($oldPrice) {
                        $price = $oldPrice;
                    }
                    if ($salePrice && !$price) {
                        $price = $salePrice;
                    }
                    if ($salePrice && $salePrice !== $price) {
                        $rowData[23] = $salePrice;
                    }
                    if ($price) {
                        $rowData[24] = $price;
                    }

                    $variations[] = $rowData;
                    $variationId++;
                }
            }
            $attributes = [];
            foreach ($attributeValues as $title => $values) {
                $attributes[] = $title;
                $attributes[] = implode(', ', array_unique($values));
                $attributes[] = '1';
                $attributes[] = '0';
            }
            $data[] = array_merge($productData, $attributes);
            $data = array_merge($data, $variations);
        }

        return $this->renderCSV($data);
    }

    /**
     * @return array<int, string>
     */
    protected function getPageData(string $type, Page $page): array
    {
        $categories = $page->getCategories();
        $paths = [];
        $isVisible = false;
        foreach ($categories as $category) {
            $isVisible |= $category->isVisible();
            $path = [];
            foreach ($category->getBranch() as $node) {
                $path[] = $node->getTitle();
            }
            array_shift($path);
            $paths[] = implode(' > ', $path);
        }

        $accessoryIds = [];
        foreach ($page->getAccessories() as $accessory) {
            $accessoryIds[] = 'id:' . $accessory->getId();
        }
        foreach ($page->getTables() as $table) {
            if (!$table->hasLinks()) {
                continue;
            }
            foreach ($table->getRows() as $row) {
                if ($row['page']) {
                    $accessoryIds[] = 'id:' . $row['page']->getId();
                }
            }
        }

        $description = $page->getHtml();
        $description = preg_replace('/<img[^>]*?>/ui', '', $description) ?: '';

        $requirement = $page->getRequirement();
        $purchaseNote = $requirement ? $requirement->getHtml() : '';

        $price = (string)$page->getPrice();
        if ($page->getOldPrice()) {
            $price = (string)$page->getOldPrice();
        }

        $salesPrice = '';
        if ($page->getOldPrice()) {
            $salesPrice = (string)$page->getPrice();
        }

        return [
            (string)$page->getId(),
            $type,
            $page->getSku() ?: ('#' . $page->getId()),
            $page->getTitle(),
            $page->isInactive() ? '0' : '1',
            '0',
            $isVisible ? 'visible' : 'hidden',
            $page->getExcerpt(),
            $description,
            '',
            '',
            'taxable',
            '',
            $page->isInactive() ? '0' : '1',
            '',
            '1',
            '1',
            '',
            '',
            '',
            '',
            '1',
            $purchaseNote,
            $salesPrice ?: '',
            $price ?: '',
            implode(', ', $paths),
            trim($page->getKeywords(), " \n\r\t\v\0,"),
            '',
            implode(', ', $this->extractImages($page)),
            'n/a',
            'n/a',
            '',
            '',
            '',
            $accessoryIds ? implode(', ', $accessoryIds) : '',
            '',
            '',
            '',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractImages(InterfaceRichText $richText): array
    {
        $html = $richText->getHtml();
        $matches = null;
        $success = preg_match_all(
            '/<img[^>]+src="([^"]+)"[^>]*>/iu',
            $html,
            $matches
        );

        if (false === $success) {
            throw new Exception('preg_replace failed');
        }

        $urls = array_filter($matches[1] ?? []);

        $result = [];
        foreach ($urls as $url) {
            $result[] = (string)new Uri(config('base_url') . $url);
        }

        return $result;
    }

    /**
     * @param array<int, array<int, string>> $data
     *
     * @throws Exception
     */
    protected function renderCSV(array $data = []): Response
    {
        $csv = fopen('php://temp', 'r+b');
        if ($csv === false) {
            throw new Exception('Failed to create buffer for CSV data.');
        }

        foreach ($data as $row) {
            fputcsv($csv, $row);
        }

        rewind($csv);

        $output = stream_get_contents($csv);

        $header = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Export-' . date('c') . '.csv' . '"',
        ];

        return new Response($output, Response::HTTP_OK, $header);
    }
}
