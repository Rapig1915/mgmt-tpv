<template>
  <div style="margin: 15px;">
    <tab-bar :active-item="3" />
    <div class="alerts-container">
      <div class="card mb-0">
        <div class="card-header">
          <h5 class="card-title mb-0 pull-left">
            Alerts
          </h5>
          <button
            class="btn btn-success btn-sm pull-right m-0"
            @click="handleAddAlert"
          >
            Add alert
          </button>
        </div>
        <custom-table
          :headers="headers"
          :data-grid="alertsData"
          :data-is-loaded="dataIsLoaded"
          show-action-buttons
          has-action-buttons
          empty-table-message="No alerts were found."
        />
      </div>
    </div>
    <div
      id="createAlertModal"
      class="modal fade"
      role="dialog"
      tabindex="-1"
    >
      <div
        class="modal-dialog modal-dialog-centered"
        role="document"
      >   
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">
              Create alert
            </h4>
          </div>
          <div class="modal-body">
            <form
              class="add-alert-form"
              @submit="handleSubmit"
            >
              <div class="row mb-2">
                <div class="col-md-6">
                  <custom-input
                    label="Start date"
                    :value="currentAlertData.startDate || ''"
                    type="date"
                    class-style="small"
                    placeholder="Start date"
                    name="start_date"
                  />
                </div>
                <div class="col-md-6">
                  <custom-input
                    label="End date"
                    :value="currentAlertData.endDate || ''"
                    type="date"
                    class-style="small"
                    placeholder="End date"
                    name="end_date"
                  />
                </div>
              </div>
              <div class="row mb-2">
                <div class="col-md-12">
                  <custom-textarea
                    label="Message"
                    :value="currentAlertData.message || ''"
                    placeholder="Message"
                    name="message"
                  />
                </div>
              </div>
              <div class="row">
                <div class="col-md-8">
                  <input
                    type="hidden"
                    name="id"
                    :value="currentAlertData.id || ''"
                  >
                  <button
                    class="btn btn-primary"
                    name="submit"
                  >
                    Save alert
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import CustomInput from 'components/CustomInput';
import CustomTextarea from 'components/CustomTextarea';
import CustomTable from 'components/CustomTable';
import TabBar from '../components/TabBar.vue';

const ROUTES = {
    ALERTS_CREATE: '/alerts/create',
    ALERTS_DELETE: '/alerts/delete',
    ALERTS_LIST: '/alerts/list',
};

const spinnerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';

export default {
    name: 'Alerts',
    components: {
        CustomInput,
        CustomTextarea,
        CustomTable,
        TabBar,
    },
    data() {
        return {
            alertsData: [],
            currentAlertData: {
                id: null,
                startDate: null,
                endDate: null,
                message: null,
            },
            headers: [
                {
                    label: 'Id',
                    key: 'id',
                    serviceKey: 'id',
                    width: '20%', 
                },
                {
                    label: 'Start date',
                    key: 'startDate',
                    serviceKey: 'start_date',
                    width: '20%', 
                },
                {
                    label: 'End date',
                    key: 'endDate',
                    serviceKey: 'end_date',
                    width: '20%',              
                },          
                {
                    label: 'Messages',
                    key: 'message',
                    serviceKey: 'message',
                    width: '20%',              
                },  
            ],
            dataIsLoaded: false,
        };
    },
    mounted() {
        this.fetch(ROUTES.ALERTS_LIST);
    },
    methods: {
        handleSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const cta = form.submit;
            cta.disabled = true;
            cta.innerHTML = `${cta.innerHTML} ${spinnerHTML}`;

            const data = {
                id: form.id.value,
                start_date: form.start_date.value,
                end_date: form.end_date.value,
                message: form.message.value,
            };

            this.saveAlert(data, () => {
                cta.disabled = false;
                cta.querySelector('.fa-spinner').remove();
                $('#createAlertModal').modal('hide');
            });
        },
        handleAddAlert() {
            const form = this.$el.querySelector('.add-alert-form');
            form.reset();
            this.currentAlertData = {};
            $('#createAlertModal').modal('show');
        },
        handleDelete(e) {
            const cta = e.target;
            const currentAlertID = cta.href.split('/').pop();
            cta.classList.add('disabled');
            cta.innerHTML = `${cta.innerHTML} ${spinnerHTML}`;

            this.deleteAlert(currentAlertID, () => {
                cta.classList.remove('disabled');
                cta.querySelector('.fa-spinner').remove();
            });
        },
        handleEdit(e) {
            const currentAlertID = e.target.href.split('/').pop();
            const currentAlertData = this.alertsData.find((alert) => +alert.id === +currentAlertID);
            this.currentAlertData = currentAlertData;
            $('#createAlertModal').modal('show');
        },
        denormalizeAlert({ data: alerts }) {
            return alerts.map((alert) => ({
                id: alert.id,
                startDate: alert.start_date,
                endDate: alert.end_date,
                message: alert.message,
                buttons: [
                    {
                        type: 'custom',
                        label: 'Edit',
                        url: `/alerts/edit/${alert.id}`,
                        classNames: 'btn-primary',
                        onClick: this.handleEdit,
                    },
                    {
                        type: 'custom',
                        label: 'Delete',
                        url: `/alerts/delete/${alert.id}`,
                        classNames: 'btn-primary',
                        onClick: this.handleDelete,
                    },
                ],
            }));
        },
        async deleteAlert(id, cb) {
            await axios.delete(ROUTES.ALERTS_DELETE, { data: { id } });
            this.fetch(ROUTES.ALERTS_LIST, cb);
        },
        async saveAlert(data, cb) {
            await axios.post(ROUTES.ALERTS_CREATE, data);
            this.fetch(ROUTES.ALERTS_LIST, cb);
        },
        async fetch(url, cb) {
            const alerts = await axios.get(url);
            this.alertsData = this.denormalizeAlert(alerts.data);
            this.dataIsLoaded = true;
            cb && cb();
        },
    },
};
</script>

<style scoped>
  .card-wrapper {
    background: #ffff;
    padding: 20px;
  }
  .card {
    margin-bottom: 0;
  }
</style>
