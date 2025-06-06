<template>
    <div class="flex overflow-hidden">
        <DashboardMenu />

        <PageTitle title="Data Analysis" />

        <div class="content content--top-nav mr-5">
            <div
                id="data-analysis"
                style="width: calc(100%); height: calc(100%)"
                class="mt-5 rounded-[35px]"
            >
                <oracle-dv
                    project-path="/@Catalog/shared/kingsway/kingsway_artisans_analytics_v1"
                    active-page="insight"
                    active-tab-id="snapshot!canvas!1"
                    project-options="{&quot;showCanvasNavigation&quot;:true, &quot;bShowFilterBar&quot;:true}"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardMenu from '@adminPages/dashboards/DashboardMenu.vue';
import { onMounted } from 'vue';

onMounted(async () => {
    const dataAnalysisId = document.getElementById('data-analysis');
    const script = document.createElement("script");
    script.src = "https://oacartisan-axylewqdodcq-si.analytics.ocp.oraclecloud.com/public/dv/v1/embedding/standalone/embedding.js";
    script.type = "application/javascript";
    script.onload = () => {
        // eslint-disable-next-line
        requirejs(
            ["knockout", "ojs/ojcore", "ojs/ojknockout", "ojs/ojcomposite", "jet-composites/oracle-dv/loader"],

            function (ko) {
                ko.cleanNode(dataAnalysisId);
                ko.applyBindings({}, dataAnalysisId);
            }
        );
    };
    document.head.appendChild(script);
});
</script>
