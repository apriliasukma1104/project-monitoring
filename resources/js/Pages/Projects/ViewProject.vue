<template>
    <layout title="View Project">
    <Toast position="top-center" />

      <Dialog header="Header" v-model:visible="dataTask.display" >
        <template #header>
                <label>New Task</label>
                </template>
                <div class="p-fluid">
                    <div class="p-field">
                        <label for="task">Task<span style="color:red;">*</span></label>
                        <InputText v-model="form.id" type="text" hidden/>
                        <InputText type="text" :value="formData.name" hidden/>
                        <InputText v-model="form.task" type="text" />
                    </div>
                    <div class="p-field">
                        <InputText v-model="form.name_member" type="text" :value="user.name" hidden disabled/>
                        <label for="description">Description<span style="color:red;">*</span></label>
                        <InputText v-model="form.description" type="text" />
                    </div>
                    <div class="p-field">
                        <label for="status">Status<span style="color:red;">*</span></label>
                          <select class="custom-select custom-select-sm" v-model="form.status">
                            <option disabled value="">Please Select One</option>
                            <option>to do</option>
                            <option>doing</option>
                            <option>done</option>
                          </select>
                    </div>
                </div>
                <template #footer>
                    <Button @click="simpanTask" label="Save" icon="pi pi-check" autofocus class="p-button-sm" />
                </template>
      </Dialog>
    
      <form>
        <div class="callout callout">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mt-3 ml-3">
                    <label class="control-label">Project Name</label>
                    <input type="text" name="id" class="form-control form-control-sm" required v-model="formData.id" hidden>
                    <div>{{ formData.name }}</div>
                </div>
                <div class="form-group mt-3 ml-3">
                    <label class="control-label">Description</label>
                    <div>{{ formData.description }}</div>
                </div>
                <div class="form-group mt-3 ml-3">
                    <label class="control-label" style="display: block; margin-top: 1rem;">Project Type</label>
                    <div>{{ formData.project_type }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mt-3 mr-3">
                    <label class="control-label" style="display: block; margin-top: 1rem;">Team Leader</label>
                    <div>{{ formData.team_leader.name }}</div>
                </div>
                <div class="form-group mt-3 mr-3">
                    <label class="control-label">Start Date</label>
                    <div>{{ formData.start_date }}</div>
                </div>
                <div class="form-group mt-3 mr-3">
                    <label class="control-label">End Date</label>
                    <div>{{ formData.end_date }}</div>
                </div>
                <div class="form-group mt-3 mr-3">
                    <label class="control-label" style="display: block; margin-top: 1rem;">Status</label>
                    <span class="badge badge-secondary" v-if="formData.status === 'pending'">pending</span>
                    <span class="badge badge-primary" v-if="formData.status === 'to do'">to do</span>
                    <span class="badge badge-info" v-if="formData.status === 'doing'">doing</span>
                    <span class="badge badge-dark" v-if="formData.status === 'submission'">submission</span>
                    <span class="badge badge-success" v-if="formData.status === 'done'">done</span>
                </div>
            </div>
        </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <span><b>Team Member/s:</b></span>
                    </div>
                    <div class="card-body p-0 mt-3 ml-3 mb-3">
                        <div style="display: flex;">
                            <div v-for="members in formData.team_members" style="margin-right: 8px; width: 80px;">
                                <div style="text-align: center;">
                                    <img
                                        :src="'/images/user.png'"
                                        width="40"
                                        height="40"
                                    />
                                    <br>
                                    <span style="font-size: 14px;">{{ members.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <span><b>Task List:</b></span>
                        <div class="card-tools">
                            <Button label="New Task" icon="pi pi-plus" class="p-button-secondary p-button-sm"  @click="newTask"/>
                        </div>
                    </div>
                    <div class="card-body p-0 ml-2 mr-2">
                        <DataTable :value="dataTask.task" :lazy="true" :rows="totalData" ref="dt" :loading="loading" responsiveLayout="scroll" >
                            <Column field="" header="No">
                              <template #body="slotProps">
                                {{ slotProps.index + 1 }}
                              </template>
                            </Column>
                            <Column field="name_member" header="From"></Column>
                            <Column field="task" header="Task"></Column>
                            <Column field="description" header="Description"></Column>
                            <Column field="status" header="Status">
                              <template #body="slotProps">
                                  <span :class="['status-badge', getStatusBadgeClass(slotProps.data.status)]">
                                      {{ slotProps.data.status }}
                                  </span>
                              </template>
                            </Column>
                            <Column :exportable="false" header="Action">
                                <template #body="slotProps">
                                    <Button @click="onEdit(slotProps.data)" icon="pi pi-pencil" class="p-button-rounded p-button-primary" v-if="user && user.name === slotProps.data.name_member" style="margin-right: 10px;" />
                                    <Button @click="onDelete(slotProps.data)" icon="pi pi-trash" class="p-button-rounded p-button-info" v-if="user && user.name === slotProps.data.name_member" />
                                </template>
                            </Column>
                            <template #empty>
                                No records found
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
      </form>
    </layout>
  </template>
  
  <script>
  import Layout from "../../Partials/Layout";
  import { usePage } from "@inertiajs/inertia-vue3";
  import { computed, reactive} from "vue";
  import { storeTasks, listTasks, updateTask, deleteTask } from '../../Api/projects.api.js';
  
  export default {
    components: {
        Layout,
    },

    setup() {
        const user = computed(() => usePage().props.value.auth.user);
        const { userType, members } = usePage().props.value;
        const data = usePage().props.value.formData;
        const tasks = usePage().props.value.tasks;

        const formData = reactive({
            id: data.id,
            name: data.name,
            project_type: data.project_type,
            team_leader: data.team_leader,
            team_members: data.team_members,
            start_date: data.start_date,
            end_date: data.end_date,
            status: data.status,
            description: data.description,
        });

        const form = reactive({
            id: "",
            name_member: user.value.name,
            task: "",
            description: "",
            status: "",
        });

        var dataTask = reactive({
            task : [],
            display: false
        });

        dataTask.task=tasks;

        const newTask = () => {
            dataTask.display = true;
            form.id = '';
            form.name_member = user.value.name;
            form.task = '';
            form.description = '';
            form.status = '';
        };

        async function loadLazyTask(form) {
            var params = {id_project:formData.id};
            const result = await listTasks( form );
            dataTask.task = result.data.data;
        };

        async function simpanTask() {
            form.id_project = formData.id;
            try {
                dataTask.display = false;  
                if (form.id) {
                    await updateTask(form); 
                    alert("Data Updated Successfully!");
                } else {
                    const response = await storeTasks(form);
                    alert("Data Saved Successfully!");
                }
                loadLazyTask(form);
            } catch (error) {
                alert("Data Failed to Save!");
            }
        };

        // async function handlePageChange(event) {
        //     const currentPage = event.page;
        //     const rowsPerPage = event.rowsPerPage;
        //     await loadLazyTask({ currentPage, rowsPerPage });
        // };

        const onEdit = (item) => {
            Object.assign(form, item);
            dataTask.display = true;
        };
        
        async function onDelete(data) {
            try {
                if (window.confirm("Are you sure you want to delete data?")) {
                    await deleteTask({ id: data.id });
                    alert("Data Deleted Successfully!");
                    loadLazyTask(data);
                }
            } catch (error) {
                console.error(error);
                alert("Data Failed to Delete!");
            }
        };

        const getStatusBadgeClass = (status) => {
            if (status === 'pending') {
                return 'badge badge-secondary';
            } else if (status === 'to do') {
                return 'badge badge-primary';
            } else if (status === 'doing') {
                return 'badge badge-info';
            } else if (status === 'submission') {
                return 'badge badge-dark';
            } else if (status === 'done') {
                return 'badge badge-success';
            } 
        }

        return {
            user,
            userType,
            members,
            formData,
            form,
            tasks,
            dataTask,
            newTask,
            loadLazyTask,
            simpanTask,
            // handlePageChange,
            onEdit,
            onDelete,
            getStatusBadgeClass  
        };
    }
  
  };
  </script>

<style scoped>
    .custom-select {
        font-size: 14px;
    }
    .status-badge {
    min-width: 50px; 
    }
</style>
  