import * as Sentry from '@sentry/vue';

const sentryDns = null;
const enviroment = import.meta.env.VITE_ENV;

export const userFeedbackForm = (app, props) => {
    Sentry.init({
        app,
        dsn: sentryDns,
        integrations: [
            new Sentry.Feedback({
                autoInject: props.autoInject,
                colorScheme: props.colorScheme ?? 'light',
                showBranding: props.showBranding ?? false,
                showName: props.showName ?? false,
                showEmail: props.showEmail ?? false,
                isNameRequired: props.isNameRequired,
                isEmailRequired: props.isEmailRequired ?? false,
                buttonLabel: props.buttonLabel,
                submitButtonLabel: props.submitButtonLabel,
                cancelButtonLabel: props.cancelButtonLabel,
                formTitle: props.formTitle,
                nameLabel: props.nameLabel,
                namePlaceholder: props.namePlaceholder,
                emailLabel: props.emailLabel,
                emailPlaceholder: props.emailPlaceholder,
                messageLabel: props.messageLabel,
                messagePlaceholder: props.messagePlaceholder,
                successMessageText: props.successMessageText,
            }),
        ],
        environment: enviroment
    });
};
