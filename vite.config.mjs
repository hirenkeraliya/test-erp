import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/js/common/services/chartHelper.js',
                    dest: 'js/',
                },
            ],
        }),
        laravel({
            input: [
                'resources/js/front/app.js',
                'resources/js/super_admin/app.js',
                'resources/js/admin/app.js',
                'resources/js/store_manager/app.js',
                'resources/js/warehouse_manager/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, '/resources/js'),
            '@commonComponents': path.resolve(
                __dirname,
                'resources/js/common/components'
            ),
            '@commonVendor': path.resolve(
                __dirname,
                'resources/js/common/vendor'
            ),
            '@commonPages': path.resolve(
                __dirname,
                'resources/js/common/pages'
            ),
            '@commonServices': path.resolve(
                __dirname,
                'resources/js/common/services'
            ),
            '@commonStores': path.resolve(
                __dirname,
                'resources/js/common/stores'
            ),

            '@superAdmin': path.resolve(__dirname, 'resources/js/super_admin'),
            '@superAdminComponents': path.resolve(
                __dirname,
                'resources/js/super_admin/components'
            ),
            '@superAdminPages': path.resolve(
                __dirname,
                'resources/js/super_admin/pages'
            ),

            '@admin': path.resolve(__dirname, 'resources/js/admin'),
            '@adminPages': path.resolve(__dirname, 'resources/js/admin/pages'),
            '@adminComponents': path.resolve(
                __dirname,
                'resources/js/admin/components'
            ),

            '@storeManager': path.resolve(
                __dirname,
                'resources/js/store_manager'
            ),
            '@storeManagerComponents': path.resolve(
                __dirname,
                'resources/js/store_manager/components'
            ),
            '@storeManagerPages': path.resolve(
                __dirname,
                'resources/js/store_manager/pages'
            ),

            '@warehouseManager': path.resolve(
                __dirname,
                'resources/js/warehouse_manager'
            ),
            '@warehouseManagerComponents': path.resolve(
                __dirname,
                'resources/js/warehouse_manager/components'
            ),
            '@warehouseManagerPages': path.resolve(
                __dirname,
                'resources/js/warehouse_manager/pages'
            ),

            ziggy: path.resolve(__dirname, 'vendor/tightenco/ziggy/src/js'),
            '@svg': path.resolve(__dirname, 'resources/js/svgs/'),
        },
    },
});
