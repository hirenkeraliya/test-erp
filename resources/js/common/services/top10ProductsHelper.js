export const getBackgroundColorForTop10Products = (index) => {
    const backgroundColors = [
        'bg-indigo-50 border-indigo-100',
        'bg-red-50 border-red-100',
        'bg-yellow-50 border-yellow-100',
        'bg-green-50 border-green-100',
        'bg-pink-50 border-pink-100',
        'bg-sky-50 border-sky-100',
        'bg-fuchsia-50 border-fuchsia-100',
        'bg-orange-50 border-orange-100',
        'bg-purple-50 border-purple-100',
        'bg-lime-50 border-lime-100'
    ];

    return backgroundColors[index] || backgroundColors[0];

};

export const getIconColorForTop10Products = (index) => {
    const iconColors = [
        'text-indigo-700',
        'text-red-700',
        'text-yellow-700',
        'text-green-700',
        'text-pink-700',
        'text-sky-700',
        'text-fuchsia-700',
        'text-orange-700',
        'text-purple-700',
        'text-lime-700'
    ];

    return iconColors[index] || iconColors[0];
};
