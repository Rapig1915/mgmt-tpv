<template>
  <div>
    <breadcrumb
      :items="[
        {name: 'Home', url: '/'},
        {name: 'Billing', url: '/billing'},
        {name: 'Invoices', active: true}
      ]"
    />
    <div class="container-fluid mt-3">
      <ul class="nav nav-tabs">
        <li class="nav-item">
          <a
            class="nav-link active"
            href="/billing"
          ><i class="fa fa-list" /> Invoices</a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link"
            href="/billing/charges"
          ><i class="fa fa-usd" /> Charges and Credits</a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link"
            href="/billing/create"
          ><i class="fa fa-plus-square" /> Generate Invoices</a>
        </li>
      </ul>
      <div class="tab-content mb-3">
        <div
          role="tabpanel"
          class="tab-pane active p-0"
        >
          <div class="animated fadeIn">
            <div class="row">
              <div class="col-md-12">
                <label class="sr-only">Brand Filter</label>
                <select
                  v-model="searchValue"
                  class="form-control pull-left"
                  placeholder="Brand Filter"
                  :style="searchValue == null ? '' : 'width:95%'"
                  @change="updateSearchValue"
                >
                  <option :value="null">
                    Select a Brand to filter...
                  </option>
                  <option
                    v-for="(item,index) in invoiceableBrands"
                    :key="index"
                    :value="item.id"
                  >
                    {{ item.name }}
                  </option>
                </select>
                <i
                  v-if="searchValue !== null"
                  class="fa fa-remove fa-2x text-danger pull-right"
                  title="Clear Brand Filter"
                  style="cursor:pointer;"
                  @click="clearSearch"
                />
                
                <div class="d-none form-group pull-right m-0 ml-1">
                  <a
                    href="/billing/charges"
                    class="btn btn-info m-0"
                  ><i
                    class="fa fa-usd"
                    aria-hidden="true"
                  /> Charges and Credits</a>
                  <a
                    :href="createUrl"
                    class="btn btn-success m-0"
                  ><i
                    class="fa fa-plus-square"
                    aria-hidden="true"
                  /> Generate Invoice(s)</a>
                </div>
                <div class="form-group form-inline pull-right">
                  <!-- <custom-input
                :value="searchValue"
                :style="{ marginRight: 0 }"
                placeholder="Search"
                name="search"
                @onChange="updateSearchValue"
                @onKeyUpEnter="searchData"
              />
              <button
                class="btn btn-primary"
                @click="searchData">
                <i class="fa fa-search"/>
              </button>-->
                </div>
              </div>
            </div>
            <div class="card pb-0 mb-0">
              <div class="d-none card-header">
                Invoices
              </div>
              <div class="card-body p-0">
                <div
                  v-if="hasFlashMessage"
                  class="alert alert-success"
                >
                  <span class="fa fa-check-circle" />
                  <em>{{ flashMessage }}</em>
                </div>
                <custom-table
                  :headers="headers"
                  :data-grid="dataGrid"
                  :data-is-loaded="dataIsLoaded"
                  show-action-buttons
                  has-action-buttons
                  empty-table-message="No invoices were found."
                  :no-bottom-padding="true"
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
  </div>
</template>

<script>
import { status, statusLabel, actions } from 'utils/constants';
import { arraySort } from 'utils/arrayManipulation';
import CustomTable from 'components/CustomTable';
import Pagination from 'components/Pagination';
import Breadcrumb from 'components/Breadcrumb';
import { mapState } from 'vuex';

const NO_SORTED = '';
const ASC_SORTED = 'asc';
const DESC_SORTED = 'desc';
const LIST_ENDPOINT = '/billing/list?';

export default {
    name: 'Invoices',
    components: {
        CustomTable,
        Pagination,
        Breadcrumb,
    },
    props: {
        invoiceableBrands: {
            type: Array,
            default() {
                return [];
            },
        },
        createUrl: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            results: [],
            headers: [
                {
                    label: 'Status',
                    key: 'status',
                    serviceKey: 'invoice_statuses.status',
                    width: '15%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'string',
                },
                {
                    label: 'Created',
                    key: 'created_at',
                    serviceKey: 'invoices.created_at',
                    width: '15%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'date',
                },
                {
                    label: 'Invoice #',
                    key: 'invoice_number',
                    serviceKey: 'invoice_number',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'string',
                },
                {
                    label: 'Invoice Range',
                    key: 'invoice_range',
                    serviceKey: 'invoice_range',
                    width: '15%',
                    sorted: NO_SORTED,
                },
                {
                    label: 'Due Date',
                    key: 'invoice_due_date',
                    serviceKey: 'invoice_due_date',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'date',
                },
                {
                    label: 'Brand Name',
                    key: 'brandName',
                    serviceKey: 'brands.name',
                    width: '15%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'string',
                },
                {
                    label: 'Client Name',
                    key: 'clientName',
                    serviceKey: 'clients.name',
                    width: '15%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'string',
                },
                {
                    label: 'Total',
                    key: 'total',
                    serviceKey: 'total',
                    width: '15%',
                    canSort: true,
                    sorted: NO_SORTED,
                    type: 'number',
                },
                {
                    label: 'Sent',
                    key: 'sent',
                    serviceKey: 'sent',
                    width: '15%',
                    canSort: true,
                    sorted: NO_SORTED,
                    
                },
                {
                    label: 'Read',
                    key: 'read',
                    serviceKey: 'read',
                    width: '15%',
                    canSort: false,
                },
            ],
            searchValue: this.getParams().search,
            dataIsLoaded: false,
            activePage: 1,
            numberPages: 1,
            isFavoritesPage: false,
            column: this.getParams().column,
            direction: this.getParams().direction,
        };
    },
    computed: {
        dataGrid: {
            get() {
                return this.results;
            },
            set(val) {
                this.results = val;
            },
        },
        ...mapState({
            hasFlashMessage: (state) => state.session && Object.keys(state.session).length && state.session.hasOwnProperty('flash_message') && state.session.flash_message,
            flashMessage: (state) => state.session.flash_message,
        }),
    },
    mounted() {
        const searchParam = this.searchValue
            ? `&search=${this.searchValue}`
            : '';
        const sortParams = !!this.column && !!this.direction
            ? `&column=${this.column}&direction=${this.direction}`
            : '';
        const pageParam = this.getParams().page ? `&page=${this.getParams().page}` : '';
        const baseUrl = LIST_ENDPOINT;

        if (!!this.column && !!this.direction) {
            const sortHeaderIndex = this.headers.findIndex(
                (header) => header.serviceKey === this.column,
            );
            this.headers[sortHeaderIndex].sorted = this.direction;
        }

        this.fetch(baseUrl, `${pageParam}${searchParam}${sortParams}`);
    },
    methods: {
        getDataObject(result) {
            let table = '';
            if (result.read) {
                table = `
        <table class="table-light table-bordered">
          <thead>
            <th scope="col">Opened</th>
            <th scope="col">Ip</th>
          </thead>
          <tbody>`;
                result.read.forEach((element) => {
                    table += `<tr><td>${element.opened}</td>`;
                    table += `<td>${element.ip}</td></tr>`;
                });
                table += '</tbody></table>';
            }
            return {
                id: result.id,
                created_at: result.created_at,
                invoice_number: result.invoice_number,
                invoice_range: `${this.$moment(result.invoice_start_date).format('YYYY-MM-DD')} to ${this.$moment(result.invoice_end_date).format('YYYY-MM-DD')}`,
                invoice_due_date: `${this.$moment(result.invoice_due_date).format('YYYY-MM-DD')}`,
                brandName: result.brand_name,
                clientName: result.client_name,
                status:
                  result.status === 'approved'
                      ? '<span class="font-weight-bold text-success">approved</span>'
                      : '<span class="font-weight-bold text-danger">not approved</span>',
                statusLabel: statusLabel[result.status === 'approved' ? 1 : 0],
                total: `$${parseFloat(result.total).toLocaleString()}`,
                totalOriginal: result.total,
                sent: result.sent == '0000-00-00 00:00:00' ? null : result.sent,
                read: table,
                buttons: [
                    {
                        type: 'edit',
                        url: `/invoice/${result.id}`,
                    },
                ],
            };
        },
        sortData(serviceKey, index) {
            this.headers[index].sorted = this.headers[index].sorted === ASC_SORTED ? DESC_SORTED : ASC_SORTED;

            let originalVal = null;
            switch (serviceKey) {
                case 'status':
                    originalVal = 'statusLabel';
                    break;
                case 'total':
                    originalVal = 'totalOriginal';
                    break;
                default:
                    break;
            }

            // Updating values for page render and export
            this.column = serviceKey;
            this.direction = this.headers[index].sorted;

            // and navigate to the new sorted page
            this.selectPage(this.page);
        },
        searchData() {
            window.location.href = `/billing?${this.getSearchParams()}${this.getSortParams()}`;
        },
        selectPage(page) {
            const baseUrl = '/billing';
            window.location.href = `${baseUrl}?${page ? `page=${page}` : ''}${this.getSortParams()}${this.getSearchParams()}`;
        },
        clearSearch() {
            this.searchValue = null;
            this.selectPage(null);
        },
        getSearchParams() {
            return this.searchValue ? `&search=${this.searchValue}` : '';
        },
        getSortParams() {
            return !!this.column && !!this.direction
                ? `&column=${this.column}&direction=${this.direction}`
                : '';
        },
        getPageParams() {
            return this.activePage ? `&page=${this.activePage}` : '';
        },
        updateSearchValue() {
            this.selectPage(null);
        },
        async fetch(baseUrl, params) {
            this.dataIsLoaded = false;
            axios
                .get(`${baseUrl}${params}`)
                .then((response) => {
                    this.results = response.data.data.map((result) => this.getDataObject(result));

                    this.dataIsLoaded = true;
                    this.activePage = response.data.current_page;
                    this.numberPages = response.data.last_page;
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        getParams() {
            const url = new URL(window.location.href);
            const startDate = url.searchParams.get('startDate')
        || this.$moment().format('YYYY-MM-DD');
            const endDate = url.searchParams.get('endDate') || this.$moment().format('YYYY-MM-DD');
            const column = url.searchParams.get('column') || '';
            const direction = url.searchParams.get('direction') || '';
            const page = url.searchParams.get('page');
            const search = url.searchParams.get('search');

            return {
                startDate,
                endDate,
                column,
                direction,
                page,
                search,
            };
        },
    },
};
</script>

<style lang="scss" scoped>
</style>
