import Tippy from '@commonComponents/Tippy.vue';
import { userFeedbackForm } from '@commonVendor/userFeedback';
import { createInertiaApp, Head, Link } from '@inertiajs/vue3';
import dom from '@left4code/tw-starter/dist/js/dom';
import Guest from '@warehouseManagerPages/layouts/Guest.vue';
import Master from '@warehouseManagerPages/layouts/Master.vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import '../../css/warehouse_manager/app.css';

const appName = import.meta.env.VITE_APP_NAME;
const pinia = createPinia();

createInertiaApp({
    title: title => title ? `${title} - ` + appName : appName,
    resolve: name => {
        // Reference: https://stackoverflow.com/questions/72864434/default-persistent-layout-in-laravel-inertia-vite
        const page = resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue')
        );
        page.then((module) => {
            module.default.layout = Master;
            if (name.startsWith('guest/')) {
                module.default.layout = Guest;
            }
        });

        return page;
    },
    setup ({ el, App, props, plugin }) {
        const { settings } = props.initialPage.props;
        dom('html').attr('class', settings.color);

        const link = document.createElement('link');
        link.rel = 'icon';
        link.href = settings.fav_icon;

        dom('head').append(link);

        let app = createApp({ render: () => h(App, props) });

        const sentryUserFeedbackFormProps = {
            isEmailRequired: true,
        };

        userFeedbackForm(app, sentryUserFeedbackFormProps);

        app.component('PageTitle', Head)
            .component('Head', Head)
            .component('Link', Link)
            .component('Tippy', Tippy)
            .use(plugin)
            .use(pinia)
            .mount(el);
    },
    progress: {
        color: '#312e81',
        showSpinner: true
    },
});
