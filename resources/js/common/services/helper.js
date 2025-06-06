import { confirmDialogBox, showErrorNotification, showSuccessNotification } from '@commonServices/notifier';
import ObjectStorage from '@commonServices/storage.js';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { format } from 'date-fns';
import { computed, toRaw } from 'vue';
const maximumLastVisitedPages = import.meta.env.VITE_MAXIMUM_LAST_VISITED_PAGES;
const pageProps = computed(() => usePage().props);

const manualRoles = [
    'custom_read_record',
    'pos_admin_read_record'
];

export const prepareImplodedNames = (locations) => {
    if (!locations) {
        return 'N/A';
    }

    return locations.map(function (location) {
        return location.name;
    }).join(', ');
};

export function recordExistsInList (records, recordId) {
    for (const key in records) {
        if (records[key].id === recordId) {
            return true;
        }
    }

    return false;
}

export const exportRecords = (route, fileName, parameters, permission = null, columns = []) => {
    if (columns.length > 0) {
        parameters['export_columns'] = columns;
    }
    if (checkExportPermission(permission)) {
        return axios.get(route + fileName, { params: parameters, responseType: 'arraybuffer' })
            .then((response) => {
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', fileName);
                document.body.appendChild(link);
                link.click();
                link.remove();
            });
    }

    showErrorNotification('User does not have any of the necessary access rights.');
};

export const isPrintRecords = (permission) => {
    if (havePermission(permission)) {
        return true;
    }

    showErrorNotification('User does not have any of the necessary access rights.');
    return false;
};

export const havePermission = (permission) => {
    if (pageProps.value.permissions === null) {
        return true;
    }

    return pageProps.value.permissions.find(record => record.includes(permission));
};

export const checkExportPermission = (permission) => {
    if (!permission || pageProps.value.permissions === null) {
        return true;
    }
    return pageProps.value.permissions.find(record => record === permission);
};

export const exportRecordsUsingRouter = (route, fileName, parameters, permission) => {
    if (havePermission(permission)) {
        return axios.get(route, { params: parameters, responseType: 'arraybuffer' })
            .then((response) => {
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', fileName);
                document.body.appendChild(link);
                link.click();
                link.remove();
            });
    }
    showErrorNotification('User does not have any of the necessary access rights.');
};

export function displayAmountWithCurrencySymbol (amount, isNegativeAmount) {
    if (amount < 0) {
        return currencySymbol() + currencyFormat(amount);
    }

    if (isNegativeAmount) {
        return '-' + currencySymbol() + currencyFormat(amount);
    }

    return currencySymbol() + currencyFormat(amount);
}

export function truncateDecimal (amount) {
    return parseFloat(numberFormat(amount).toString());
}

export function displayAmountWithPercentageSymbol (amount) {
    return numberFormat(amount) + '%';
}

export const numberFormat = (number) => {
    const precision = 100;
    return (Math.round((parseFloat(number) + Number.EPSILON) * precision) / precision).toFixed(decimalPlaces);
};

const defaultFractionDigits = 2;

export const currencyFormat = (number, minimumFractionDigits = defaultFractionDigits) => {
    return parseFloat(numberFormat(number)).toLocaleString('en-US', { minimumFractionDigits });
};

export const currentDate = () => {
    const milliSeconds = 60000;
    return new Date(new Date().getTime() - new Date().getTimezoneOffset() * milliSeconds)
        .toISOString()
        .split('T')[0];
};

export const formatDateAsMMMYYYY = (dateString) => {
    const months = {
        0: 'January',
        1: 'February',
        2: 'March',
        3: 'April',
        4: 'May',
        5: 'June',
        6: 'July',
        7: 'August',
        8: 'September',
        9: 'October',
        10: 'November',
        11: 'December'
    };
    const date = new Date(dateString);
    return months[date.getMonth()] + ' ' + date.getFullYear();
};

export const printHtml = () => {
    // Reference: https://stackoverflow.com/a/57498674
    const printWindow = window.open('', 'Receipt', 'height=400,width=600');
    printWindow.document.write(document.getElementById('print-receipt-container').innerHTML);
    printWindow.document.close(); // necessary for IE >= 10

    printWindow.onload = () => {
        printWindow.focus(); // necessary for IE >= 10
        printWindow.print();
    };
};

export const printReport = (url, permission = null) => {
    if (checkExportPermission(permission)) {
        const printWindow = window.open(url, '_blank');

        printWindow.onload = () => {
            printWindow.focus(); // necessary for IE >= 10
            printWindow.print();
        };

        return;
    }

    showErrorNotification('User does not have any of the necessary access rights.');
};

export const printReportForChart = (url, permission = null) => {
    if (checkExportPermission(permission)) {
        const printWindow = window.open(url, '_blank');
        const printTimeout = 1500;

        printWindow.onload = () => {
            setTimeout(() => {
                printWindow.focus(); // necessary for IE >= 10
                printWindow.print();
            }, printTimeout);
        };

        return;
    }

    showErrorNotification('User does not have any of the necessary access rights.');
};

const decimalPlaces = 2;

// https://stackoverflow.com/questions/48193258/use-parsefloat-inside-of-an-array
export const getTotalOf = (object, column) => {
    return object.reduce((a, b) => parseFloat(b[column]) > 0.00 ? a + parseFloat(b[column]) : a, 0).toFixed(decimalPlaces);
};

export const paramToSentenceCase = (input) => {
    return !input
        ? ''
        : input.split('_')
            .map((word) => {
                return word[0].toUpperCase() + word.substr(1).toLowerCase();
            }).join(' ');
};

export const objectArrayToString = (items, separator = '') => {
    if (!items) return;
    const toDisplay = [];
    for (const key in items) {
        toDisplay.push(paramToSentenceCase(key) + ': ' + items[key]);
    }

    return toDisplay.join(separator);
};

export const clearSelectedProductData = (route, id, type = null) => {
    confirmDialogBox('Do you want to clear the selected products?', () => {
        axios.post(route, {
            id,
            type,
        }).then(() => {
            showSuccessNotification('The selected products have been removed successfully.');
            window.location.reload();
        });
    });
};

export function capitalize (string) {
    if (!string) {
        return;
    }

    return string.split('_')
        .map((word) => {
            return word[0].toUpperCase() + word.substr(1).toLowerCase();
        }).join(' ');
}

export function getDisplayableColumns (columns) {
    return Object.values(columns).filter((column) => {
        return column.isDisplay;
    });
}

export function areColumnsCustomized (originalColumns, localStorageColumns) {
    if (originalColumns.length !== localStorageColumns.length) {
        return true;
    }

    for (const key in originalColumns) {
        let isColumnFound = false;

        for (const localStorageColumnKey in localStorageColumns) {
            const originalColumn = Object.assign({}, originalColumns[key]);
            delete originalColumn.isDisplay;

            const localStorageColumn = Object.assign({}, localStorageColumns[localStorageColumnKey]);
            delete localStorageColumn.isDisplay;

            if (JSON.stringify(originalColumn) === JSON.stringify(localStorageColumn)) {
                isColumnFound = true;
            }
        }

        if (!isColumnFound) {
            return true;
        }
    }

    return false;
}

export const currentDateTime = () => {
    const endOfDayHour = 23;
    const endOfDayMinute = 59;
    const endOfDaySecond = 59;
    return [format(new Date().setHours(0, 0, 0), 'yyyy-MM-dd HH:mm:ss'), format(new Date().setHours(endOfDayHour, endOfDayMinute, endOfDaySecond), 'yyyy-MM-dd HH:mm:ss')];
};

export const currentSingleDateTime = () => {
    return format(new Date(), 'yyyy-MM-dd HH:mm:ss');
};

export const getDateByAddDays = (addDays) => {
    const currentDate = new Date();
    const result = currentDate.setDate(currentDate.getDate() + addDays);
    return format(new Date(result), 'dd MMMM yyyy');
};

// Ref: https://stackoverflow.com/questions/16239513/print-pdf-directly-from-javascript
export const printPdf = (url, permission = null) => {
    if (checkExportPermission(permission)) {
        const iframe = document.createElement('iframe');
        document.body.appendChild(iframe);
        iframe.style.display = 'none';
        iframe.onload = function () {
            setTimeout(function () {
                iframe.focus();
                iframe.contentWindow.print();
                URL.revokeObjectURL(url);
            }, 1);
        };
        iframe.src = url;

        return;
    }

    showErrorNotification('User does not have any of the necessary access rights.');
};

export const hasPermission = (permissionName) => {
    const permissions = ObjectStorage.get('permissions');

    // Allow user that doesn't have any roles.
    if (permissions === null || !permissions.length) {
        return true;
    }

    const notFound = -1;

    return permissions.indexOf(permissionName) !== notFound;
};

export const toTitleCase = (inputText) => {
    return inputText.charAt(0).toUpperCase() + inputText.slice(1).toLowerCase();
};

export const getRGBColorsOfPrimaryColor = () => {
    const colorPrimary = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
    const bgOpacity = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--tw-bg-opacity'));
    const rgbValues = colorPrimary.match(/\d+/g);

    const [red, green, blue] = rgbValues.map(value => Math.round(parseInt(value) / bgOpacity));

    return `rgb(${red}, ${green}, ${blue})`;
};

export const removeLocalStorage = (name) => {
    localStorage.removeItem(name);
};

export const setLocalStorage = (name, form) => {
    const storage = JSON.parse(localStorage.getItem(name));
    if (storage !== null) {
        Object.keys(storage).forEach(key => {
            const value = storage[key];
            form[key] = value;
        });
    }
};

export const saveLocalStorage = (name, form) => {
    localStorage.setItem(name, JSON.stringify(form));
};

export const getPastelColors = () => {
    return [
        '#fcd792', '#feb560', '#ffc7c4', '#f8b3d1', '#f79bc4', '#f68fc1', '#fe71bb', '#cd98cd', '#c36fb8', '#bab2e4', '#999ed5', '#b2d5eb', '#97c4e1', '#7bb5de', '#90d4bd'
    ];
};

export const getRandomPastelColor = () => {
    const backgroundColor = getPastelColors();

    backgroundColor.forEach((value, key) => {
        const randomIndex = Math.floor(Math.random() * (backgroundColor.length - key) + key);
        [backgroundColor[key], backgroundColor[randomIndex]] = [backgroundColor[randomIndex], value];
    });

    return backgroundColor;
};

const orderOfMagnitudeBase = 3;
const logBase = 10;

export const formatLabelForChart = (value) => {
    if (value < 1) {
        return '';
    }

    const suffixes = ['', 'K', 'M', 'B', 'T']; // Define suffixes for each order of magnitude
    const order = Math.floor(Math.log10(value) / orderOfMagnitudeBase); // Determine order of magnitude

    const suffix = suffixes[order]; // Get suffix for order of magnitude
    const scaledValue = value / Math.pow(logBase, order * orderOfMagnitudeBase); // Scale value by order of magnitude

    return truncateDecimal(scaledValue) + suffix;
};

export const formatLabelForDashboardWithCurrencySymbol = (value) => {
    if (value < 1) {
        return '';
    }

    const suffixes = ['', 'K', 'M', 'B', 'T']; // Define suffixes for each order of magnitude
    const order = Math.floor(Math.log10(value) / orderOfMagnitudeBase); // Determine order of magnitude

    const suffix = suffixes[order]; // Get suffix for order of magnitude
    const scaledValue = value / Math.pow(logBase, order * orderOfMagnitudeBase); // Scale value by order of magnitude

    return currencySymbol() + truncateDecimal(scaledValue) + suffix;
};

export const formatYAxisLabelForChart = (value) => {
    const suffixes = ['', 'K', 'M', 'B', 'T'];

    if (value === 0) return '0'; // Handle special case
    const tier = Math.log10(value) / orderOfMagnitudeBase | 0;
    const suffix = suffixes[tier];
    const scale = Math.pow(logBase, tier * orderOfMagnitudeBase);
    const scaledValue = value / scale;
    return scaledValue + suffix;
};

export const formatYAxisLabelForChartWithCurrencySymbol = (value) => {
    const suffixes = ['', 'K', 'M', 'B', 'T'];

    if (value === 0) return '0'; // Handle special case
    const order = Math.floor(Math.log10(value) / orderOfMagnitudeBase);
    const suffix = suffixes[order];
    const scaledValue = value / Math.pow(logBase, order * orderOfMagnitudeBase);
    return currencySymbol() + parseInt(scaledValue) + suffix;
};

export function displayAmountWithCurrencySymbolToFourDigit (amount, isNegativeAmount) {
    if (amount < 0) {
        return currencySymbol() + currencyFormatToFourDigit(amount);
    }

    if (isNegativeAmount) {
        return '-' + currencySymbol() + currencyFormatToFourDigit(amount);
    }

    return currencySymbol() + currencyFormatToFourDigit(amount);
}

const defaultMinimumFractionDigits = 4;

export const currencyFormatToFourDigit = (number, minimumFractionDigits = defaultMinimumFractionDigits) => {
    return parseFloat(numberFormatToFourDigit(number)).toLocaleString('en-US', { minimumFractionDigits });
};

export const numberFormatToFourDigit = (number) => {
    const decimalPrecision = 10000;
    const decimalPlaces = 4;
    return (Math.round((parseFloat(number) + Number.EPSILON) * decimalPrecision) / decimalPrecision).toFixed(decimalPlaces);
};

export const lastVisitedPage = (panel) => {
    let storage = getStorageArrayFromLocalStorage(panel);
    const pageInfo = getPageInfo();

    storage = removeExtraPages(storage, maximumLastVisitedPages, panel);

    if (!pageInfo.title || pageInfo.title === 'Login') {
        return;
    }

    if (isPageTitleSameAsLastVisited(panel, pageInfo.title)) {
        return;
    }

    const existsPage = storage.filter(page => page.title === pageInfo.title);

    if (existsPage.length > 0) {
        storage = storage.filter(page => page.title !== pageInfo.title);
    }

    trimStorageArray(storage, maximumLastVisitedPages);
    storage.push(pageInfo);
    localStorageSetItem(panel, storage);
};

const getPageInfo = () => {
    const pageTitle = document.title;
    let splitPageTitle = pageTitle.split(' - ')[0];
    const url = window.location.href;

    if (splitPageTitle === 'Menus') {
        const urlBasename = decodeURIComponent(url.split('/').pop());
        splitPageTitle = urlBasename;
    }

    return {
        url,
        title: splitPageTitle
    };
};

const isPageTitleSameAsLastVisited = (panel, pageTitle) => {
    const storedItems = getStorageArrayFromLocalStorage(panel);
    const lastIndex = storedItems.length - 1;

    return lastIndex >= 0 && storedItems[lastIndex].title === pageTitle;
};

const getStorageArrayFromLocalStorage = (key) => {
    const storedItem = JSON.parse(localStorage.getItem(key));
    return Array.isArray(storedItem) ? storedItem : [];
};

const trimStorageArray = (array, maxLength) => {
    if (array.length >= maxLength) {
        array.shift();
    }
};

const removeExtraPages = (array, maxLength, panel) => {
    if (array.length > maxLength) {
        const totalSplice = (array.length - maxLength);
        array.splice(0, totalSplice);
        localStorageSetItem(panel, array);
    }

    return array;
};

const localStorageSetItem = (panel, storage) => {
    localStorage.setItem(panel, JSON.stringify(storage));
};

const getUserPermissions = () => {
    if (Array.isArray(ObjectStorage.get('permissions'))) {
        return [...manualRoles, ...ObjectStorage.get('permissions')];
    }

    return [...manualRoles];
};

const hasPermissionName = (permissionName, userPermission = getUserPermissions()) => {
    return userPermission.includes(permissionName);
};

export const canAccess = (permissionName) => {
    if (pageProps.value.environment === 'local') {
        return true;
    }

    if (permissionName === 'custom_read_record' || permissionName === 'pos_admin_read_record') {
        return true;
    }

    return hasPermissionName(permissionName);
};

export const checkMenuPermission = (subMenus) => {
    const permissions = getUserPermissions();

    const displaySection = subMenus.some(subMenu => {
        if (Object.keys(subMenu).includes('subSubSubMenu')) {
            return subMenu.subSubSubMenu.some(subSubMenu => {
                return permissions.includes(subSubMenu.permission);
            });
        }

        return permissions.includes(subMenu.permission);
    });

    return displaySection;
};

export const checkMobileMenuPermission = (menu, hasPermission) => {
    if (pageProps.value.environment === 'local') {
        return true;
    }

    const permissions = getUserPermissions();

    if (menu === undefined) {
        return true;
    }

    if (!hasPermission) {
        return true;
    }

    const checkPermissions = (menu) => {
        if (menu === undefined) {
            return true;
        }

        if (Array.isArray(menu)) {
            return menu.some(item => checkPermissions(item));
        }

        if (menu.permission !== undefined && menu.subMenu === undefined) {
            return permissions.includes(menu.permission);
        }

        if (menu.subMenu !== undefined) {
            return checkPermissions(menu.subMenu);
        }

        if (menu.subSubMenu !== undefined) {
            return checkPermissions(menu.subSubMenu);
        }

        if (menu.subSubSubMenu !== undefined) {
            return checkPermissions(menu.subSubSubMenu);
        }

        return true;
    };

    return checkPermissions(menu);
};

export const checkMainMenuPermission = (menu) => {
    const permissions = getUserPermissions();

    if (menu.permission !== undefined && menu.subMenu === undefined) {
        return permissions.includes(menu.permission);
    }

    if (pageProps.value.permissions === null || menu.subMenu === undefined) {
        return true;
    }

    const displaySection = menu.subMenu.some(subMenu => {
        if (Object.keys(subMenu).includes('subSubMenu')) {
            return subMenu.subSubMenu.some(subSubMenu => {
                if (Object.keys(subSubMenu).includes('subSubSubMenu')) {
                    return subSubMenu.subSubSubMenu.some(subSubSubMenu => {
                        return permissions.includes(subSubSubMenu.permission);
                    });
                }
                return permissions.includes(subSubMenu.permission);
            });
        }
        return permissions.includes(subMenu.permission);
    });

    return displaySection;
};

export const convertProxyObjToNormalObj = (ProxyObj) => {
    if (ProxyObj) {
        if (Array.isArray(ProxyObj)) {
            return ProxyObj.map((obj) => toRaw(obj));
        }
        return toRaw(ProxyObj);
    }
    return null;
};

export const checkEInvoicePermission = (eInvoiceGeneratePermission) => {
    if (pageProps.value.permissions === null) {
        return true;
    }

    return pageProps.value.permissions.find(record => record.includes(eInvoiceGeneratePermission));
};

export const currencySymbol = () => {
    return pageProps.value.currency_symbol;
};

const lowProgressThreshold = 40;
const mediumProgressThreshold = 80;
const maxProgress = 100;

export const getStockTransferShipmentProgress = (progressPercentage) => {
    if (progressPercentage < lowProgressThreshold) {
        return `linear-gradient(to right, white, yellow ${progressPercentage}%)`;
    } else if (progressPercentage < mediumProgressThreshold) {
        return `linear-gradient(to right, white, orange ${progressPercentage}%)`;
    } else if (progressPercentage <= maxProgress) {
        return `linear-gradient(to right, white, green ${progressPercentage}%)`;
    } else {
        return `linear-gradient(to right, white, red ${progressPercentage}%)`;
    }
};

export const sellThroughReportFilterValidationCheck = (parameters, isLocationCompulsorySelection) => {
    if (!parameters.report_type || !parameters.main_report_type) {
        return false;
    }

    const dateSelected = parameters.date || parameters.date_range;
    if (!dateSelected) {
        return false;
    }

    const hasAnyParameterSelected = (
        parameters.product_id !== null ||
        parameters.product_collection_id !== null ||
        parameters.category_id !== null ||
        parameters.brand_id !== null ||
        parameters.size_id !== null ||
        parameters.color_ids !== null ||
        parameters.department_ids !== null ||
        (parameters.article_numbers && parameters.article_numbers.length > 0) ||
        parameters.tag_ids !== null ||
        parameters.style_ids !== null || parameters.attributes !== null
    );

    if (isLocationCompulsorySelection && parameters.location_ids.length === 0) {
        return false;
    }

    return hasAnyParameterSelected;
};

export const stockMovementSummaryReportFilterValidationCheck = (parameters) => {
    if (!parameters.report_type) {
        return false;
    }

    const dateSelected = parameters.date || parameters.date_range;
    if (!dateSelected) {
        return false;
    }

    const hasAnyParameterSelected = (
        parameters.product_id !== null ||
        parameters.product_collection_id !== null ||
        parameters.category_id !== null ||
        parameters.brand_id !== null ||
        parameters.size_id !== null ||
        parameters.color_ids !== null ||
        parameters.department_ids !== null ||
        (parameters.article_numbers && parameters.article_numbers.length > 0) ||
        parameters.tag_ids !== null ||
        parameters.style_ids !== null || parameters.attributes !== null
    );

    return hasAnyParameterSelected;
};

export const filterMenusByPermissions = (menus) => {
    const permissions = ObjectStorage.get('permissions');

    if (permissions) {
        menus = menus.filter((menu) => {
            return !menu.permission || permissions.includes(menu.permission);
        });
    }

    return menus;
};
