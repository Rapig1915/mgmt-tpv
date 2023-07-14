export default {
    tab: 'uninvoiced',
    search: '',
    browserState: null,
    exportUrl: '',
    chargesData: [],
    clientsData: [{
        value: '',
        label: 'Select option',
    }],
    categoriesData: [{
        value: '',
        label: 'Select option',
    }],
    brandsData: [{ value: '', label: 'Select option' }],
    currentChargeData: {
        id: null,
        owner: '',
        ticket: '',
        category: '',
        duration: '',
        date_of_work: '',
        description: '',
        client: '',
    },
    headers: [
        /* {
            label: 'Id',
            key: 'id',
            serviceKey: 'id',
            width: '10%',
        },*/
        {
            label: 'Updated At',
            key: 'updated_at',
            serviceKey: 'updated_at',
            canSort: true,
            sorted: '',
        },
        {
            label: 'Owner',
            key: 'owner',
            serviceKey: 'owner',
            width: '20%',
            canSort: true,
            sorted: '',
        },
        {
            label: 'Brand',
            key: 'brand',
            serviceKey: 'brand',
            width: '20%',
            canSort: true,
            sorted: '',
        },
        {
            label: 'Ticket',
            key: 'ticket',
            serviceKey: 'ticket',
            width: '10%',
            canSort: true,
            sorted: '',
        },
        {
            label: 'Category',
            key: 'category',
            serviceKey: 'category',
            width: '20%',
            canSort: true,
            sorted: '',
        },
        {
            label: 'Duration/Qty',
            key: 'duration',
            serviceKey: 'duration',
            width: '10%',
            canSort: true,
            sorted: '',
        },
        {
            label: 'Total',
            key: 'total',
            serviceKey: 'total',
            canSort: false,
            sorted: '',
        },
        {
            label: 'Date of work',
            key: 'date_of_work',
            serviceKey: 'date_of_work',
            width: '10%',
            canSort: true,
            sorted: '',
        },
        {
            label: 'Description',
            key: 'short_desc',
            serviceKey: 'short_desc',
            width: '20%',
            canSort: true,
            sorted: '',
        },

    ],
    dataIsLoaded: false,
    activePage: 1,
    numberPages: 1,
    paramsObj: null,
    validationErrors: [],
};
