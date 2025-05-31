export const formatUTCDate = (dateStr: string | Date) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

export const convertLocalDateToUTCString = (date: Date) => {
    return new Date(date).toISOString().replace('T', ' ').substring(0, 19);
};

export const convertUTCStringToLocalDate = (dateStr: string) => {
    return new Date(dateStr);
};
