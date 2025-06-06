const base = 10;
const scaleFactor = 3;

// eslint-disable-next-line no-unused-vars
function formatLabelForChart(value) {
    if (value < 1) {
        return '';
    }

    const suffixes = ['', 'K', 'M', 'B', 'T']; // Define suffixes for each order of magnitude
    const order = Math.floor(Math.log10(value) / scaleFactor); // Determine order of magnitude

    const suffix = suffixes[order]; // Get suffix for order of magnitude
    const scaledValue = value / Math.pow(base, order * scaleFactor); // Scale value by order of magnitude

    return truncateDecimal(scaledValue) + suffix;
};

// eslint-disable-next-line no-unused-vars
function formatLabelAndValueForChart (name, value) {
    if (value < 1) {
        return '';
    }

    const suffixes = ['', 'K', 'M', 'B', 'T']; // Define suffixes for each order of magnitude
    const order = Math.floor(Math.log10(value) / scaleFactor); // Determine order of magnitude

    const suffix = suffixes[order]; // Get suffix for order of magnitude
    const scaledValue = value / Math.pow(base, order * scaleFactor); // Scale value by order of magnitude

    return name + ' \n ' + truncateDecimal(scaledValue) + suffix;
};

const defaultTruncateLength = 8;

// eslint-disable-next-line no-unused-vars
function labelsInTruncateForm(name, truncateUpTo = defaultTruncateLength) {
    const maxNameLength = 4;
    if (name.length > maxNameLength) {
        return name.substring(0, truncateUpTo) + '...';
    }

    return name;
};

// eslint-disable-next-line no-unused-vars
function formatLabelAndValueForChartWithPercentage (name, value) {
    if (value < 1) {
        return '';
    }

    return name + ' \n ' + truncateDecimal(value) + '%';
};

// eslint-disable-next-line no-unused-vars
function formatLabelForChartWithPercentage (value) {
    if (value < 1) {
        return '';
    }

    return truncateDecimal(value) + '%';
};

const truncateDecimal = (amount) => {
    return parseFloat(numberFormat(amount).toString());
};

const numberFormat = (number) => {
    const precision = 100;
    const decimalPlaces = 2;
    return (Math.round((parseFloat(number) + Number.EPSILON) * precision) / precision).toFixed(decimalPlaces);
};

// eslint-disable-next-line no-unused-vars
function formatYAxisLabelForChart (value) {
    const suffixes = ['', 'K', 'M', 'B', 'T'];
    const logBase = 10;
    const scaleFactor = 3;

    if (value === 0) return '0'; // Handle special case
    const tier = Math.log10(value) / scaleFactor | 0;
    const suffix = suffixes[tier];
    const scale = Math.pow(logBase, tier * scaleFactor);
    const scaledValue = value / scale;

    return scaledValue + suffix;
};

// eslint-disable-next-line no-unused-vars
function getPastelColors () {
    return [
        '#fcd792', '#feb560', '#ffc7c4', '#f8b3d1', '#f79bc4', '#f68fc1', '#fe71bb', '#cd98cd', '#c36fb8', '#bab2e4', '#999ed5', '#b2d5eb', '#97c4e1', '#7bb5de', '#90d4bd'
    ];
};
