<template>
  <div id="main-app">
    <breadcrumb
      :items="[
        {name: 'Home', url: '/'},
        {name: 'Reports', url: '/reports'},
        {name: `${ getParams().uploadTypeId == 3 ? 'Contracts' : 'Photos' } Report`, url: '/reports/report_contracts?upload_type_id=3', active: true}
      ]"
    />

    <div class="page-buttons filter-bar-row">
      <nav class="navbar navbar-light bg-light filter-navbar">
        <search-form
          :on-submit="searchData"
          :initial-values="initialSearchValues"
          :hide-search-box="true"
          include-date-range
          include-brand
          include-vendor
          include-channel
          include-language
          include-state
        >
          <div class="form-group pull-right m-0">
            <a
              :href="exportUrl"
              class="btn btn-primary m-0"
              :class="{'disabled': !contracts.length}"
            ><i
              class="fa fa-download"
              aria-hidden="true"
            /> Export Data</a>
          </div>
        </search-form>
      </nav>
    </div>

    <div class="container-fluid">
      <div class="animated fadeIn">
        <br class="clearfix">
        <br>
        <div class="card">
          <div class="card-header">
            <i class="fa fa-th-large" /> Report: {{ getParams().uploadTypeId == 3 ? 'Contracts' : 'Photos' }}
          </div>
          <div class="row mt-5 p-3">
            <div class="col-md-12">
              <!-- <a class="btn btn-success pull-right mt-2 mb-2" :href="exportUrl"> Export Table</a> -->
              <custom-table
                :headers="headers"
                :data-grid="contracts"
                :data-is-loaded="dataIsLoaded"
                :total-records="totalRecords"
                :empty-table-message="`No ${getParams().uploadTypeId == 3 ? 'contracts' : 'photos'} were found.`"
                @sortedByColumn="sortData"
              />
              <pagination
                v-if="dataIsLoaded"
                :active-page="activePage"
                :number-pages="numberPages"
                @onSelectPage="selectPage"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CustomTable from 'components/CustomTable';
import SearchForm from 'components/SearchForm';
import Pagination from 'components/Pagination';
import { arraySort } from 'utils/arrayManipulation';
import { formArrayQueryParam } from 'utils/stringHelpers';
import Breadcrumb from 'components/Breadcrumb';

const NO_SORTED = '';
const ASC_SORTED = 'asc';
const DESC_SORTED = 'desc';

export default {
    name: 'ReportContract',
    components: {
        CustomTable,
        SearchForm,
        Pagination,
        Breadcrumb,
    },
    props: {
        channels: {
            type: Array,
            default: () => [],
        },
        brands: {
            type: Array,
            default: () => [],
        },
        languages: {
            type: Array,
            default: () => [],
        },
        vendors: {
            type: Array,
            default: () => [],
        },
        states: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            totalRecords: 0,
            contracts: [],
            headers: [
                {
                    label: 'Brand',
                    align: 'left',
                    key: 'name',
                    serviceKey: 'name',
                    width: '14%',
                    canSort: true,
                    sorted: ASC_SORTED,
                    type: 'string',
                },
                {
                    label: 'Vendor',
                    align: 'left',
                    key: 'vendor_name',
                    serviceKey: 'vendor_name',
                    width: '14%',
                    canSort: true,
                    sorted: ASC_SORTED,
                    type: 'string',
                },
                {
                    label: 'Created At',
                    align: 'center',
                    key: 'created_at',
                    serviceKey: 'created_at',
                    width: '30%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'date',
                },
                {
                    label: 'Channel',
                    align: 'center',
                    key: 'channel',
                    serviceKey: 'channel',
                    width: '14%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'string',
                },
                {
                    label: 'State',
                    align: 'center',
                    key: 'state_abbrev',
                    serviceKey: 'state_abbrev',
                    width: '14%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'string',
                },
                {
                    label: 'Language',
                    align: 'center',
                    key: 'language',
                    serviceKey: 'language',
                    width: '14%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'string',
                },
                {
                    label: 'Confirmation Code',
                    align: 'center',
                    key: 'confirmation_code',
                    serviceKey: 'confirmation_code',
                    width: '14%',
                    canSort: false,
                    sorted: NO_SORTED,
                    type: 'string',
                },
            ],
            dataIsLoaded: false,
            displaySearchBar: false,
            activePage: 1,
            numberPages: 1,
            column: this.getParams().column,
            direction: this.getParams().direction,
        };
    },
    computed: {
        exportUrl() {
            return `/reports/list_contracts?${this.filterParams}${this.sortParams}&csv=true`;
        },
        sortParams() {
            return !!this.column && !!this.direction
                ? `&column=${this.column}&direction=${this.direction}`
                : '';
        },
        filterParams() {
            const params = this.getParams();
            return [
                params.startDate ? `&startDate=${params.startDate}` : '',
                params.endDate ? `&endDate=${params.endDate}` : '',
                params.channel ? formArrayQueryParam('channel', params.channel) : '',
                params.brand ? formArrayQueryParam('brand', params.brand) : '',
                params.language ? formArrayQueryParam('language', params.language) : '',
                params.state ? formArrayQueryParam('state', params.state) : '',
                params.vendor ? formArrayQueryParam('vendor', params.vendor) : '',
                params.uploadTypeId ? `&upload_type_id=${params.uploadTypeId}` : '',
            ].join('');
        },
        initialSearchValues() {
            const params = this.getParams();
            return {
                startDate: params.startDate,
                endDate: params.endDate,
                channel: params.channel,
                brand: params.brand,
                language: params.language,
                state: params.state,
                vendor: params.vendor,
            };
        },
    },
    created() {
        this.$store.commit('setStates', this.states);
        this.$store.commit('setBrands', this.brands);
        this.$store.commit('setChannels', this.channels);
        this.$store.commit('setLanguages', this.languages);
        this.$store.commit('setVendors', this.vendors);
    },
    mounted() {
        const params = this.getParams();
        document.title += ` Report: ${params.uploadTypeId == 3 ? 'Contracts' : 'Photos'}`;
        const pageParam = params.page ? `&page=${params.page}` : '';

        axios
            .get(
                `/reports/list_contracts?${pageParam}${this.filterParams}${this.sortParams}`,
            )
            .then((response) => {
                this.dataIsLoaded = true;
                const res = response.data;
                this.contracts = res.data.map((contract) => {
                    contract.confirmation_code = `<a href="/events/${contract.event_id}">${contract.confirmation_code}</a>`;
                    return contract;
                });

                this.totalRecords = res.total;
                this.activePage = res.current_page;
                this.numberPages = res.last_page;
            })
            .catch(console.log);
    },
    methods: {
        searchData({
            startDate,
            endDate,
            channel,
            brand,
            language,
            vendor,
            state,
        }) {
            const params = this.getParams();
            const filterParams = [
                startDate ? `&startDate=${startDate}` : '',
                endDate ? `&endDate=${endDate}` : '',
                channel ? formArrayQueryParam('channel', channel) : '',
                brand ? formArrayQueryParam('brand', brand) : '',
                vendor ? formArrayQueryParam('market', vendor) : '',
                language ? formArrayQueryParam('language', language) : '',
                state ? formArrayQueryParam('state', state) : '',
                params.uploadTypeId ? `&upload_type_id=${params.uploadTypeId}` : '',
            ].join('');
            window.location.href = `/reports/report_contracts?${filterParams}${this.sortParams}`;
        },
        sortData(serviceKey, index) {
            this.headers[index].sorted = this.headers[index].sorted === ASC_SORTED ? DESC_SORTED : ASC_SORTED;

            this.contracts = arraySort(
                this.headers,
                this.contracts,
                serviceKey,
                index,
            );

            // Updating values for page render and export
            this.column = serviceKey;
            this.direction = this.headers[index].sorted;
        },
        selectPage(page) {
            window.location.href = `/reports/report_contracts?page=${page}${this.filterParams}`;
        },
        getParams() {
            const url = new URL(window.location.href);
            const startDate = url.searchParams.get('startDate')
        || this.$moment().format('YYYY-MM-DD');
            const endDate = url.searchParams.get('endDate') || this.$moment().format('YYYY-MM-DD');
            const column = url.searchParams.get('column') || '';
            const direction = url.searchParams.get('direction') || '';
            const page = url.searchParams.get('page');
            const uploadTypeId = url.searchParams.get('upload_type_id') || 3;
            const brand = url.searchParams.getAll('brand[]');
            const vendor = url.searchParams.getAll('vendor[]');
            const state = url.searchParams.getAll('state[]');
            const channel = url.searchParams.getAll('channel[]');
            const language = url.searchParams.getAll('language[]');

            return {
                startDate,
                endDate,
                column,
                direction,
                page,
                brand,
                vendor,
                state,
                channel,
                language,
                uploadTypeId,
            };
        },
    },
};
</script>
