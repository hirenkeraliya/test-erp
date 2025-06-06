<template>
    <PageTitle title="Dashboard" />

    <div class="mt-5">
        <h1 class="mr-auto text-xl font-semibold uppercase">
            Welcome to {{ appName }} Warehouse Manager Panel
        </h1>
    </div>
</template>
<script setup>
const appName = import.meta.env.VITE_APP_NAME;
</script>
