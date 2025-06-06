import alertify from 'alertifyjs';

alertify.set('notifier', 'position', 'top-right');

export const confirmDialogBox = (message, onSuccess, onCancel = function () {}) => {
    alertify.confirm(
        'Confirmation',
        message,
        onSuccess,
        onCancel
    ).set(
        'labels', { ok: 'Yes', cancel: 'No' }
    );
};

export const confirmDialogBoxWithCenterText = (message, onSuccess, onCancel = function () {}) => {
    alertify.confirm(
        'Confirmation',
        message,
        onSuccess,
        onCancel
    ).set(
        'labels', { ok: 'Yes', cancel: 'No' }
    );

    const footer = alertify.confirm().elements.footer;
    const primaryButtons = footer.querySelector('.ajs-primary.ajs-buttons');
    if (primaryButtons) {
        primaryButtons.classList.add('text-center-important');
    }

    alertify.confirm().elements.header.classList.add('text-center-important');

    alertify.confirm().elements.content.classList.add('text-center-important');
};

export const showSuccessNotification = (message) => {
    let timer; const notification = alertify.success(message);
    delayMessage(timer, notification);
};

export const showErrorNotification = (message) => {
    let timer; const notification = alertify.error(message);
    delayMessage(timer, notification);
};

const delayMessage = (timer, notification) => {
    const delay = alertify.get('notifier', 'delay');
    if (delay) {
        const delayInterval = 600;
        notification.element.addEventListener('mouseover', function () {
            notification.delay(delay);

            timer = setInterval(function () {
                notification.delay(delay);
            }, delayInterval * delay);
        });

        notification.element.addEventListener('mouseout', function () {
            clearInterval(timer);
        });
    }
};
