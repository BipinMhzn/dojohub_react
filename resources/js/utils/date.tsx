export const formatUTCDate = (dateStr: string) =>
    new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });


export const convertLocalDateToUTCString = (date: Date) => {
    return new Date(date).toISOString().replace('T', ' ').substring(0, 19)
};
