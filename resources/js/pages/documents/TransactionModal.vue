 
<script setup>
import { ref, reactive } from 'vue';
import { Form } from 'vee-validate';
import axios from 'axios';
import { onMounted } from 'vue';
import { useToastr } from '../../toastr';

const toastr = useToastr();

const props = defineProps({
    isVisible: Boolean,
    formData: Object,
    transaction: Object,
})

const emits = defineEmits(['closeModal', 'submitForm']);

const closeModal = () => {
    emits('closeModal');
    resetForm();
};

const resetForm = () => {
    props.formData.value = {
        employee_id: '',
        action: '',
        file: null,
    };
};

const getFile = (event) => {
    props.formData.document_file = event.target.files[0];
}

const employees = ref();

const getEmployees = () => {
    axios.get('/api/employees')
        .then((response) => {
            employees.value = response.data;
        });
}
const createRouteDocument = () => {
    const formData = new FormData();
    formData.append('document_id', props.formData.document_id);
    formData.append('employee_id', props.formData.employee_id);
    formData.append('action', props.formData.action);
    formData.append('document_file', props.formData.document_file);

    axios.post(`/api/documents/transaction/${props.transaction.id}/edit`, formData)
        .then((response) => {
            toastr.success('Document routed successfully');
            closeModal();
        });


    console.log('Form submitted with data:', props.formData);
    console.log(props.transaction.id);
    // emits('submitForm', formData.value);    
};

onMounted(() => {
    getEmployees();
})
</script>
  
<template>
    <div v-if="isVisible" class="modal" role="dialog" tabindex="1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <Form @submit="createRouteDocument">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span>Edit transaction {{ transaction.id }}</span>
                        </h5>
                        <span class="close" @click="closeModal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="employee">Employee/Office</label>
                                    <select v-model="formData.employee_id" id="employee_id" name="employee_id"
                                        class="form-control">
                                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{
                                            employee.emp_name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="action">Action</label>
                                    <input v-model="formData.action" type="text" class="form-control" id="action"
                                        name="action">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="file" class="form-control-file" id="document_file" name="document_file"
                                @change="getFile" />
                            <span>{{ formData.document_file }}</span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit">Submit</button>
                    </div>
                </Form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal {
    display: block;
}
</style>
  