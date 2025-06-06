<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Season" />

        <div class="content content--top-nav mr-5">
            <div class="flex flex-col lg:flex-row mt-5">
                <div class="col-span-12 lg:col-span-4 md:col-span-4">
                    <FormSelectBox
                        class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                        :selected-record="state.parameters.sale_season_id"
                        :records="saleSeasons"
                        :placeholder="'Please select Season'"
                        @update:selected-record="updateSaleSeasonId($event)"
                    />
                </div>

                <div
                    v-if="state.parameters.sale_season_id"
                    class="col-span-12 lg:col-span-4 md:col-span-4"
                >
                    <FormSelectBox
                        v-model:selected-record="state.parameters.location_id"
                        class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                        :records="locations"
                        :placeholder="'Please select Locations'"
                        @update:selected-record="updateLocationId($event)"
                    />
                </div>

                <div
                    v-if="state.parameters.sale_season_id"
                    class="col-span-12 lg:col-span-4 md:col-span-4"
                >
                    <FormSelectBox
                        class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                        :selected-record="state.parameters.brand_id"
                        :records="brands"
                        :placeholder="'Please select Brands'"
                        @update:selected-record="updateBrandId($event)"
                    />
                </div>
            </div>

            <div class="mt-10">
                <div
                    v-if="state.parameters.sale_season_id"
                    class="col-span-12"
                >
                    <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-5">
                        <div class="col-span-12 lg:col-span-5 md:col-span-5">
                            <div class="grid grid-rows-1 gap-3 sm:grid-rows-2 sm:gap-4 lg:grid-rows-2 items-center">
                                <DashboardCard
                                    title="Sales"
                                    :amount="state.totalSales"
                                    :is-data-fetching="state.totalSales === 0"
                                />

                                <DashboardCard
                                    title="Orders"
                                    :quantity="state.totalReceipt"
                                    :is-data-fetching="state.totalReceipt === 0"
                                />

                                <DashboardCard
                                    title="Units Sold"
                                    :quantity="state.totalUnitsSold"
                                    :is-data-fetching="state.totalUnitsSold === 0"
                                />

                                <DashboardCard
                                    title="UPT"
                                    :quantity="state.upt"
                                    :is-data-fetching="state.upt === 0"
                                />

                                <DashboardCard
                                    title="ATV"
                                    :amount="state.atv"
                                    :is-data-fetching="state.atv === 0"
                                />

                                <DashboardCard
                                    title="Discount"
                                    :amount="state.totalDiscounts"
                                    :is-data-fetching="state.totalDiscounts === 0"
                                />
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-7 md:col-span-7">
                            <BarOrLineChart
                                chart-height="30vh"
                                chart-id="seasonal-by-brand-chart"
                                title-of-chart="Brand"
                                title-class="mx-auto"
                                :data="isNotEmpty(state.byBrandChartData.data) ? state.byBrandChartData.data : []"
                                :labels="state.byBrandChartData.labels"
                                :filters="filters"
                            />

                            <BarOrLineChart
                                class="mt-10"
                                chart-height="30vh"
                                chart-id="seasonal-by-region-chart"
                                title-of-chart="Region"
                                title-class="mx-auto"
                                :data="isNotEmpty(state.byRegionChartData.data) ? state.byRegionChartData.data : []"
                                :labels="state.byRegionChartData.labels"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-12 md:col-span-12 mt-10">
                            <MultiBarOrLineChart
                                chart-id="weekly-sales-and-order-chart"
                                title-of-chart="Weekly Sales Performance: Sales vs Orders"
                                :datasets="isNotEmpty(state.bySaleWeekChartData.data) ? state.bySaleWeekChartData.data : []"
                                :labels="state.bySaleWeekChartData.labels"
                                :legend-data="state.bySaleWeekChartData.legendData"
                                :background-color="isNotEmpty(state.bySaleWeekChartData.data)"
                                file-name="weekly-sales-and-order-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-12 md:col-span-12 mt-10">
                            <MultiBarOrLineChart
                                chart-id="location-wise-sales-and-order-chart"
                                title-of-chart="Location Performance: Sales vs Orders"
                                :datasets="isNotEmpty(state.bySaleLocationChartData.data) ? state.bySaleLocationChartData.data : []"
                                :labels="state.bySaleLocationChartData.code_based_labels"
                                :legend-data="state.bySaleLocationChartData.legendData"
                                :background-color="isNotEmpty(state.bySaleLocationChartData.data)"
                                file-name="location-wise-sales-and-order-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <MultiBarOrLineChart
                                chart-id="top-five-seasonal-color-bar-chart"
                                title-of-chart="Top 5 Colors"
                                :datasets="isNotEmpty(state.bySeasonalColorChartData.data) ? state.bySeasonalColorChartData.data : []"
                                :labels="state.bySeasonalColorChartData.labels"
                                :legend-data="state.bySeasonalColorChartData.legendData"
                                :background-color="isNotEmpty(state.bySeasonalColorChartData.data)"
                                file-name="top-five-seasonal-color-bar-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <MultiBarOrLineChart
                                chart-id="top-five-seasonal-category-bar-chart"
                                title-of-chart="Top 5 Categories"
                                :datasets="isNotEmpty(state.bySeasonalCategoryChartData.data) ? state.bySeasonalCategoryChartData.data : []"
                                :labels="state.bySeasonalCategoryChartData.labels"
                                :legend-data="state.bySeasonalCategoryChartData.legendData"
                                :background-color="isNotEmpty(state.bySeasonalCategoryChartData.data)"
                                file-name="top-five-seasonal-category-bar-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <MultiBarOrLineChart
                                chart-id="top-five-seasonal-style-bar-chart"
                                title-of-chart="Top 5 Styles"
                                :datasets="isNotEmpty(state.bySeasonalStyleChartData.data) ? state.bySeasonalStyleChartData.data : []"
                                :labels="state.bySeasonalStyleChartData.labels"
                                :legend-data="state.bySeasonalStyleChartData.legendData"
                                :background-color="isNotEmpty(state.bySeasonalStyleChartData.data)"
                                file-name="top-five-seasonal-style-bar-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <MultiBarOrLineChart
                                chart-id="top-five-seasonal-department-bar-chart"
                                title-of-chart="Top 5 Departments"
                                :datasets="isNotEmpty(state.bySeasonalDepartmentChartData.data) ? state.bySeasonalDepartmentChartData.data : []"
                                :labels="state.bySeasonalDepartmentChartData.labels"
                                :legend-data="state.bySeasonalDepartmentChartData.legendData"
                                :background-color="isNotEmpty(state.bySeasonalDepartmentChartData.data)"
                                file-name="top-five-seasonal-department-bar-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <MultiBarOrLineChart
                                chart-id="top-five-seasonal-color-group-bar-chart"
                                title-of-chart="Top 5 Color Groups"
                                :datasets="isNotEmpty(state.bySeasonalColorGroupChartData.data) ? state.bySeasonalColorGroupChartData.data : []"
                                :labels="state.bySeasonalColorGroupChartData.labels"
                                :legend-data="state.bySeasonalColorGroupChartData.legendData"
                                :background-color="isNotEmpty(state.bySeasonalColorGroupChartData.data)"
                                file-name="top-five-seasonal-color-group-bar-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <MultiBarOrLineChart
                                chart-id="top-five-seasonal-size-bar-chart"
                                title-of-chart="Top 5 Sizes"
                                :datasets="isNotEmpty(state.bySeasonalSizeChartData.data) ? state.bySeasonalSizeChartData.data : []"
                                :labels="state.bySeasonalSizeChartData.labels"
                                :legend-data="state.bySeasonalSizeChartData.legendData"
                                :background-color="isNotEmpty(state.bySeasonalSizeChartData.data)"
                                file-name="top-five-seasonal-size-bar-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <BarOrLineChart
                                chart-id="week-based-color-chart"
                                title-class="mx-auto"
                                title-of-chart="Weekly Sales"
                                :data="isNotEmpty(state.bySeasonalWeekBasedColorChartData.data) ? state.bySeasonalWeekBasedColorChartData.data : []"
                                data-set-label="Units Sold"
                                legend-data-position="bottom"
                                :labels="state.bySeasonalWeekBasedColorChartData.labels"
                                :background-color="isNotEmpty(state.bySeasonalWeekBasedColorChartData.data)"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                            <MultiBarOrLineChart
                                chart-id="size-with-stock-based-bar-chart"
                                title-of-chart="Sales By Size"
                                :datasets="isNotEmpty(state.sizeWithStockChartData.data) ? state.sizeWithStockChartData.data : []"
                                :labels="state.sizeWithStockChartData.labels"
                                :legend-data="state.sizeWithStockChartData.legendData"
                                :background-color="isNotEmpty(state.sizeWithStockChartData.data)"
                                file-name="size-with-stock-based-bar-chart"
                                :show-bar-and-line-chart="true"
                                :filters="filters"
                            />
                        </div>

                        <div class="col-span-12 mt-10 bg-white rounded-xl p-6">
                            <JSimpleTable
                                :columns="state.discountSummaryColumns"
                                :records="state.discountSummary"
                                :is-data-fetching="true"
                                table-classes="table overflow-hidden border-0 border-none rounded-md mb-3"
                                row-classes="border-b-2 border-slate-300"
                            >
                                <template #title="data">
                                    <a
                                        class="cursor-pointer w-full block"
                                        @click="showTotalDiscountSubDetailsModal(data.item.sub_details)"
                                    >
                                        {{ data.item.title }}
                                    </a>
                                </template>

                                <template #usages="data">
                                    <a
                                        class="cursor-pointer w-full block"
                                        @click="showTotalDiscountSubDetailsModal(data.item.sub_details)"
                                    >
                                        {{ data.item.usages }}
                                    </a>
                                </template>

                                <template #amount="data">
                                    <a
                                        class="cursor-pointer w-full block"
                                        @click="showTotalDiscountSubDetailsModal(data.item.sub_details)"
                                    >
                                        {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                                    </a>
                                </template>
                            </JSimpleTable>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="state.parameters.sale_season_id"
                class="mt-10"
            >
                <div class="bg-slate-200 rounded-xl p-5">
                    <div class="text-xl font-medium text-center">
                        Seasonal Comparison
                    </div>

                    <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3">
                        <div class="col-span-12 lg:col-span-6 md:col-span-4">
                            <FormSelectBox
                                class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                                :selected-record="state.parameters.comparison_x_sale_season_id"
                                :records="saleSeasons"
                                :placeholder="'Please select X Season'"
                                @update:selected-record="updateXComparisonSaleSeasonId($event)"
                            />
                        </div>

                        <div class="col-span-12 lg:col-span-6 md:col-span-4">
                            <FormSelectBox
                                class="w-full mt-0 mr-2 2xl:w-96 md:w-72 sm:w-60"
                                :selected-record="state.parameters.comparison_y_sale_season_id"
                                :records="saleSeasons"
                                :placeholder="'Please select Y Season'"
                                @update:selected-record="updateYComparisonSaleSeasonId($event)"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-10">
                    <div
                        v-if="state.parameters.comparison_x_sale_season_id && state.parameters.comparison_y_sale_season_id"
                        class="col-span-12 bg-white rounded-xl"
                    >
                        <MultiBarOrLineChart
                            chart-id="seasonal-comparison-data-line-chart"
                            title-of-chart="Sales By Season"
                            :datasets="isNotEmpty(state.seasonalComparisonData.data) ? state.seasonalComparisonData.data : [0]"
                            :labels="state.seasonalComparisonData.labels"
                            :legend-data="state.seasonalComparisonData.legendData"
                            :background-color="isNotEmpty(state.seasonalComparisonData.data)"
                            file-name="seasonal-comparison-data-bar"
                            :show-bar-and-line-chart="true"
                            :filters="filters"
                        />
                    </div>

                    <div
                        v-if="state.parameters.comparison_x_sale_season_id && state.parameters.comparison_y_sale_season_id"
                        class="col-span-12 bg-white rounded-xl mt-10"
                    >
                        <MultiBarOrLineChart
                            chart-id="seasonal-sales-comparison-data-line-chart"
                            title-of-chart="Pre-Season Sales"
                            :datasets="isNotEmpty(state.salesComparisonData.data) ? state.salesComparisonData.data : [0]"
                            :labels="state.salesComparisonData.labels"
                            :legend-data="state.salesComparisonData.legendData"
                            :background-color="isNotEmpty(state.salesComparisonData.data)"
                            file-name="seasonal-sales-comparison-data"
                            :show-bar-and-line-chart="true"
                            :text-rotation="0"
                            :filters="filters"
                        />
                    </div>

                    <div
                        v-if="state.parameters.comparison_x_sale_season_id && state.parameters.comparison_y_sale_season_id"
                        class="col-span-12 bg-white rounded-xl mt-10"
                    >
                        <MultiBarOrLineChart
                            chart-id="seasonal-members-comparison-data-line-chart"
                            title-of-chart="Members Registration"
                            :datasets="isNotEmpty(state.seasonalMemberComparisonData.data) ? state.seasonalMemberComparisonData.data : [0]"
                            :labels="state.seasonalMemberComparisonData.labels"
                            :legend-data="state.seasonalMemberComparisonData.legendData"
                            :background-color="isNotEmpty(state.seasonalMemberComparisonData.data)"
                            file-name="seasonal-members-comparison-data"
                            :show-bar-and-line-chart="true"
                            :text-rotation="0"
                            :filters="filters"
                        />
                    </div>

                    <div
                        v-if="state.parameters.comparison_x_sale_season_id && state.parameters.comparison_y_sale_season_id"
                        class="col-span-12"
                    >
                        <div class="grid grid-cols-12 gap-0 lg:gap-14 gap-y-3 lg:gap-y-3 mt-5 rounded-xl">
                            <div class="col-span-12 lg:col-span-6 md:col-span-6 bg-white rounded-xl mt-10">
                                <MultiBarOrLineChart
                                    chart-id="top-five-comparison-seasonal-color-bar-chart"
                                    title-of-chart="Comparison By Colors"
                                    :datasets="isNotEmpty(state.comparisonColorTopFiveChart.data) ? state.comparisonColorTopFiveChart.data : []"
                                    :labels="state.comparisonColorTopFiveChart.labels"
                                    :legend-data="state.comparisonColorTopFiveChart.legendData"
                                    :background-color="isNotEmpty(state.comparisonColorTopFiveChart.data)"
                                    file-name="top-five-comparison-seasonal-color-bar-chart"
                                    :show-bar-and-line-chart="true"
                                    :filters="filters"
                                />
                            </div>

                            <div class="col-span-12 lg:col-span-6 md:col-span-6 bg-white rounded-xl mt-10">
                                <MultiBarOrLineChart
                                    chart-id="top-five-comparison-seasonal-category-bar-chart"
                                    title-of-chart="Comparison By Categories"
                                    :datasets="isNotEmpty(state.comparisonCategoryTopFiveChart.data) ? state.comparisonCategoryTopFiveChart.data : []"
                                    :labels="state.comparisonCategoryTopFiveChart.labels"
                                    :legend-data="state.comparisonCategoryTopFiveChart.legendData"
                                    :background-color="isNotEmpty(state.comparisonCategoryTopFiveChart.data)"
                                    file-name="top-five-comparison-seasonal-category-bar-chart"
                                    :show-bar-and-line-chart="true"
                                    :filters="filters"
                                />
                            </div>

                            <div class="col-span-12 lg:col-span-6 md:col-span-6 bg-white rounded-xl mt-10">
                                <MultiBarOrLineChart
                                    chart-id="top-five-comparison-seasonal-style-bar-chart"
                                    title-of-chart="Comparison By Styles"
                                    :datasets="isNotEmpty(state.comparisonStyleTopFiveChart.data) ? state.comparisonStyleTopFiveChart.data : []"
                                    :labels="state.comparisonStyleTopFiveChart.labels"
                                    :legend-data="state.comparisonStyleTopFiveChart.legendData"
                                    :background-color="isNotEmpty(state.comparisonStyleTopFiveChart.data)"
                                    file-name="top-five-comparison-seasonal-style-bar-chart"
                                    :show-bar-and-line-chart="true"
                                    :filters="filters"
                                />
                            </div>

                            <div class="col-span-12 lg:col-span-6 md:col-span-6 bg-white rounded-xl mt-10">
                                <MultiBarOrLineChart
                                    chart-id="top-five-comparison-seasonal-department-bar-chart"
                                    title-of-chart="Comparison By Departments"
                                    :datasets="isNotEmpty(state.comparisonDepartmentTopFiveChart.data) ? state.comparisonDepartmentTopFiveChart.data : []"
                                    :labels="state.comparisonDepartmentTopFiveChart.labels"
                                    :legend-data="state.comparisonDepartmentTopFiveChart.legendData"
                                    :background-color="isNotEmpty(state.comparisonDepartmentTopFiveChart.data)"
                                    file-name="top-five-comparison-seasonal-department-bar-chart"
                                    :show-bar-and-line-chart="true"
                                    :filters="filters"
                                />
                            </div>

                            <div class="col-span-12 lg:col-span-6 md:col-span-6 bg-white rounded-xl mt-10">
                                <MultiBarOrLineChart
                                    chart-id="top-five-comparison-seasonal-color-group-bar-chart"
                                    title-of-chart="Comparison By Color Groups"
                                    :datasets="isNotEmpty(state.comparisonColorGroupTopFiveChart.data) ? state.comparisonColorGroupTopFiveChart.data : []"
                                    :labels="state.comparisonColorGroupTopFiveChart.labels"
                                    :legend-data="state.comparisonColorGroupTopFiveChart.legendData"
                                    :background-color="isNotEmpty(state.comparisonColorGroupTopFiveChart.data)"
                                    file-name="top-five-comparison-seasonal-color-group-bar-chart"
                                    :show-bar-and-line-chart="true"
                                    :filters="filters"
                                />
                            </div>

                            <div class="col-span-12 lg:col-span-6 md:col-span-6 mt-10">
                                <MultiBarOrLineChart
                                    chart-id="top-five-comparison-seasonal-size-bar-chart"
                                    title-of-chart="Comparison By Sizes"
                                    :datasets="isNotEmpty(state.comparisonSizeTopFiveChart.data) ? state.comparisonSizeTopFiveChart.data : []"
                                    :labels="state.comparisonSizeTopFiveChart.labels"
                                    :legend-data="state.comparisonSizeTopFiveChart.legendData"
                                    :background-color="isNotEmpty(state.comparisonSizeTopFiveChart.data)"
                                    file-name="top-five-comparison-seasonal-size-bar-chart"
                                    :show-bar-and-line-chart="true"
                                    :filters="filters"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="state.parameters.comparison_x_sale_season_id && state.parameters.comparison_y_sale_season_id && Object.keys(state.seasonalComparisonData).length > 0"
                        class="col-span-12 bg-white rounded-xl p-6 mt-10"
                    >
                        <div class="font-medium text-xl mb-5">
                            Summary
                        </div>
                        <div class="overflow-x-auto">
                            <table
                                class="table mx-auto max-w-full overflow-hidden border-0 border-none rounded-md mb-3"
                            >
                                <thead>
                                    <tr>
                                        <th class="border-0 border-none bg-slate-300 text-left">
                                            Title
                                        </th>

                                        <th class="border-0 border-none bg-slate-300 text-right">
                                            {{ state.seasonalComparisonData.legendData[0] }}
                                        </th>

                                        <th class="border-0 border-none bg-slate-300 text-right">
                                            {{ state.seasonalComparisonData.legendData[1] }}
                                        </th>

                                        <th class="border-0 border-none bg-slate-300 text-right">
                                            Performance
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(comparisonRecord, index) in state.comparisonRecords"
                                        :key="index"
                                    >
                                        <td class="text-left border-0 border-none bg-slate-200">
                                            {{ comparisonRecord.title }}
                                        </td>

                                        <td class="text-right border-0 border-none bg-slate-200">
                                            {{ comparisonRecord.x }}
                                        </td>

                                        <td class="text-right border-0 border-none bg-slate-200">
                                            {{ comparisonRecord.y }}
                                        </td>

                                        <td
                                            v-if="comparisonRecord.performance == 0"
                                            class="text-right border-0 border-none bg-slate-200"
                                        >
                                            {{ comparisonRecord.performance }}%
                                        </td>
                                        <td
                                            v-else
                                            class="text-right border-0 border-none bg-slate-200"
                                            :class="comparisonRecord.performance > 0 ? 'text-success' : 'text-danger'"
                                        >
                                            {{ comparisonRecord.performance }}%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal
        size="modal-xl"
        :show="state.totalDiscountSubDetailsModalShow"
        @hidden="hideTotalDiscountSubDetailsModal"
    >
        <ModalHeader>
            <h2 class="font-medium text-base mr-auto pr-8">
                Sub Details
            </h2>

            <a
                class="absolute right-0 top-0 mt-2 mr-3"
                href="javascript:;"
                @click="hideTotalDiscountSubDetailsModal"
            >
                <X class="w-7 h-7 sm:w-8 sm:h-8 text-slate-400" />
            </a>
        </ModalHeader>

        <ModalBody class="p-5 sm:p-10 text-left">
            <JSimpleTable
                :columns="state.totalDiscountsSubDetailsColumns"
                :records="state.totalDiscountsSubDetailsRecords"
                :allow-search="true"
            >
                <template #amount="data">
                    {{ displayAmountWithCurrencySymbol(data.item.amount) }}
                </template>
            </JSimpleTable>
        </ModalBody>
    </Modal>
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import BarOrLineChart from '@commonComponents/BarOrLineChart.vue';
import DashboardCard from '@commonComponents/DashboardCard.vue';
import FormSelectBox from '@commonComponents/FormSelectBox.vue';
import JSimpleTable from '@commonComponents/JSimpleTable.vue';
import MultiBarOrLineChart from '@commonComponents/MultiBarOrLineChart.vue';
import { displayAmountWithCurrencySymbol } from '@commonServices/helper';
import { Modal, ModalBody, ModalHeader } from '@commonVendor/model';
import axios from 'axios';
import { X } from 'lucide-vue-next';
import { reactive } from 'vue';
import { route } from 'ziggy';

const props = defineProps({
    saleSeasons: {
        type: Array,
        required: true,
    },
    brands: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    }
});


const state = reactive({
    parameters: {
        sale_season_id: null,
        location_id: 0,
        brand_id: 0,
        comparison_x_sale_season_id: null,
        comparison_y_sale_season_id: null,
    },
    totalDiscountSubDetailsModalShow: false,
    totalSales: 0,
    totalReceipt: 0,
    totalUnitsSold: 0,
    upt: 0,
    atv: 0,
    totalDiscounts: 0,

    comparisonRecords: [],

    discountSummaryColumns: [
        {
            key: 'title',
            bodyClass: 'border-0 border-none bg-slate-200 text-left',
            headerClass: 'border-0 border-none bg-slate-300 text-left',
        }, {
            key: 'usages',
            label: 'Redeemed',
            bodyClass: 'border-0 border-none bg-slate-200 text-right',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            sortable: true,
        }, {
            key: 'amount',
            label: 'Amount',
            bodyClass: 'border-0 border-none bg-slate-200 text-right',
            headerClass: 'border-0 border-none bg-slate-300 text-right',
            sortable: true,
        },
    ],

    totalDiscountsSubDetailsRecords: [],
    totalDiscountsSubDetailsColumns: [
        {
            key: 'title',
            label: 'Title',
            bodyClass: 'text-left',
            headerClass: 'text-left',
        }, {
            key: 'usages',
            label: 'Usage',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        }, {
            key: 'amount',
            label: 'Amount',
            bodyClass: 'text-right',
            headerClass: 'text-right',
            sortable: true,
        },
    ],
    byBrandChartData: {},
    bySaleWeekChartData: {},
    bySaleLocationChartData: {},
    xSeasonalComparisonChartData: {},
    ySeasonalComparisonChartData: {},

    byRegionChartData: {},
    discountSummary: [],

    seasonalComparisonData: {},
    salesComparisonData: {},
    bySeasonalColorChartData: {},
    bySeasonalCategoryChartData: {},
    bySeasonalSizeChartData: {},
    bySeasonalDepartmentChartData: {},
    bySeasonalColorGroupChartData: {},
    bySeasonalStyleChartData: {},
    bySeasonalWeekBasedColorChartData: {},
    sizeWithStockChartData: {},
    comparisonColorTopFiveChart: {},
    comparisonCategoryTopFiveChart: {},
    comparisonStyleTopFiveChart: {},
    comparisonDepartmentTopFiveChart: {},
    comparisonColorGroupTopFiveChart: {},
    comparisonSizeTopFiveChart: {},
    seasonalMemberComparisonData: {},
});

const filters = reactive({
    location: { name: props.locations.find(location => state.parameters.location_id === location.id)?.name || 'All' },
    brand: { name: props.brands.find(brand => state.parameters.brand_id === brand.id)?.name || 'All' },
});

const isNotEmpty = (object) => {
    if (typeof (object) === 'object') {
        return Object.keys(object).length !== 0;
    }
};

const updateSaleSeasonId = (saleSeasonId) => {
    state.parameters.sale_season_id = saleSeasonId;
    state.parameters.comparison_x_sale_season_id = saleSeasonId;
    getSeasonalSalesData();
};

const updateXComparisonSaleSeasonId = (saleSeasonId) => {
    state.parameters.comparison_x_sale_season_id = saleSeasonId;
    getSeasonalSaleComparisonChartData();
};

const updateYComparisonSaleSeasonId = (saleSeasonId) => {
    state.parameters.comparison_y_sale_season_id = saleSeasonId;
    getSeasonalSaleComparisonChartData();
};

const getSeasonalSalesData = () => {
    clearData();
    axios.get(route('admin.get_seasonal_data', { ...state.parameters }))
        .then((response) => {
            state.totalSales = response.data.sales;
            state.totalReceipt = response.data.total_receipt;
            state.totalUnitsSold = response.data.total_units_sold;
            state.upt = response.data.upt;
            state.atv = response.data.atv;
            state.totalDiscounts = response.data.total_discounts;
        });

    axios.get(route('admin.get_seasonal_chart_data', { ...state.parameters }))
        .then((response) => {
            state.byBrandChartData = response.data.brand_wise_chart_data;
            state.byRegionChartData = response.data.region_wise_chart_data;
            state.bySeasonalWeekBasedColorChartData = response.data.week_based_color_chart;
            state.sizeWithStockChartData = response.data.stock_with_size_chart;

            state.bySeasonalColorChartData = response.data.color_top_five_chart;
            state.bySeasonalCategoryChartData = response.data.category_top_five_chart;
            state.bySeasonalSizeChartData = response.data.size_top_five_chart;
            state.bySeasonalDepartmentChartData = response.data.department_top_five_chart;
            state.bySeasonalStyleChartData = response.data.style_top_five_chart;
            state.bySeasonalColorGroupChartData = response.data.color_group_top_five_chart;

            state.bySaleWeekChartData = response.data.sale_week_wise_chart_data;
            state.bySaleLocationChartData = response.data.sale_store_wise_chart_data;
        });

    axios.get(route('admin.get_seasonal_total_discounts', { ...state.parameters }))
        .then((response) => {
            state.discountSummary = response.data.discounts;
        });

    getSeasonalSaleComparisonChartData();
};

const getSeasonalSaleComparisonChartData = () => {
    if (state.parameters.comparison_x_sale_season_id === null) {
        return;
    }

    if (state.parameters.comparison_y_sale_season_id === null) {
        return;
    }

    axios.get(route('admin.get_seasonal_comparison_data', { ...state.parameters }))
        .then((response) => {
            state.seasonalComparisonData = response.data.comparisonChartData;
            state.comparisonRecords = response.data.comparisonData;
        });

    axios.get(route('admin.get_seasonal_member_comparison_data', { ...state.parameters }))
        .then((response) => {
            state.seasonalMemberComparisonData = response.data.comparisonSeasonalMemberChartData;
        });

    axios.get(route('admin.get_seasonal_sales_comparison_data', { ...state.parameters }))
        .then((response) => {
            state.salesComparisonData = response.data.comparisonSeasonalSalesChartData;
        });

    axios.get(route('admin.get_seasonal_sales_comparison_chart_data', { ...state.parameters }))
        .then((response) => {
            state.comparisonColorTopFiveChart = response.data.comparison_color_top_five_chart;
            state.comparisonCategoryTopFiveChart = response.data.comparison_category_top_five_chart;
            state.comparisonStyleTopFiveChart = response.data.comparison_style_top_five_chart;
            state.comparisonDepartmentTopFiveChart = response.data.comparison_department_top_five_chart;
            state.comparisonColorGroupTopFiveChart = response.data.comparison_color_group_top_five_chart;
            state.comparisonSizeTopFiveChart = response.data.comparison_size_top_five_chart;
        });
};

const updateLocationId = (locationId) => {
    state.parameters.location_id = locationId;
    getSeasonalSalesData();
};

const updateBrandId = (brandId) => {
    state.parameters.brand_id = brandId;
    getSeasonalSalesData();
};

const clearData = () => {
    state.totalSales = 0;
    state.totalReceipt = 0;
    state.totalUnitsSold = 0;
    state.upt = 0;
    state.atv = 0;
    state.totalDiscounts = 0;
    state.byBrandChartData = {};
    state.byRegionChartData = {};
    state.bySeasonalColorChartData = {};
    state.bySeasonalCategoryChartData = {};
    state.bySeasonalSizeChartData = {};
    state.bySeasonalDepartmentChartData = {};
    state.bySeasonalColorGroupChartData = {};
    state.bySeasonalStyleChartData = {};
    state.bySeasonalWeekBasedColorChartData = {};
    state.sizeWithStockChartData = {};
    state.comparisonColorTopFiveChart = {};
    state.comparisonCategoryTopFiveChart = {};
    state.comparisonStyleTopFiveChart = {};
    state.comparisonDepartmentTopFiveChart = {};
    state.comparisonColorGroupTopFiveChart = {};
    state.comparisonSizeTopFiveChart = {};
    state.bySaleWeekChartData = {};
    state.bySaleLocationChartData = {};
    state.discountSummary = [];
};

const hideTotalDiscountSubDetailsModal = () => {
    state.totalDiscountSubDetailsModalShow = false;
};

const showTotalDiscountSubDetailsModal = (totalDiscountDetails) => {
    state.totalDiscountsSubDetailsRecords = totalDiscountDetails;
    state.totalDiscountSubDetailsModalShow = true;
};

</script>
