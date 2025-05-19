<script setup>
import axios from 'axios';
import {ref, onMounted, reactive, watch} from 'vue';
import {Form, Field, useResetForm} from 'vee-validate';
import * as yup from 'yup';
import {useToastr} from '../../toastr.js';
import UserListItem from './UserListItem.vue';
import {debounce} from 'lodash';
import { Bootstrap4Pagination } from 'laravel-vue-pagination';

    const toastr = useToastr();
    const users = ref({'data': []});
    const editing = ref(false);
    const formValues = ref();
    const form = ref(null);
    const employees = ref();

    const getEmployees = () => {
        axios.get('/api/employees')
            .then((response) => {
                employees.value = response.data;
            });
    }

    const getUsers = (page = 1) => {
        axios.get(`/api/users?page=${page}`, {
            params: {
                query: searchQuery.value
            }
        })
            .then((response) => {
                users.value = response.data;
                selectedUsers.value = [];            
                selectAll.value = false;
            })
    }

    const createUserSchema = yup.object({
        employee: yup.string().required(),
        name: yup.string().required(),
        email: yup.string().email().required(),
        password: yup.string().required().min(8),
    });

    const editUserSchema = yup.object({
        employee: yup.string().required(),
        name: yup.string().required(),
        email: yup.string().email().required(),
        password: yup.string().when((password, schema) => {
            return password ? schema.min(8) : schema;
        }),
    });
    
    const createUser = (values, { resetForm, setErrors}) => {
        axios.post('/api/users', values)
            .then((response) => {
                users.value.data.unshift(response.data);
                $('#userFormModal').modal('hide');
                resetForm();
                toastr.success('User added successfully');
            })
            .catch((error) => {
                setErrors(error.response.data.errors);
            })
    };

    const addUser = () => {
        editing.value = false;
        $('#userFormModal').modal('show');
    };

    const addEmployee = () => {
        $('#employeeFormModal').modal('show');
    }

    const editUser = (user) => {
        editing.value = true;
        form.value.resetForm();
        $('#userFormModal').modal('show');
        formValues.value = {
            id: user.id,
            employee: user.employee,
            name: user.name,
            email: user.email,            
        };
    };

    const updateUser = (values, {setErrors}) => {
        axios.put('/api/users/' + formValues.value.id, values)
            .then((response) => {
                const index = users.value.data.findIndex(user => user.id === response.data.id);
                users.value.data[index] = response.data;
                $('#userFormModal').modal('hide');
                toastr.success('User updated successfully');
            }).catch((error) => {
                setErrors(error.response.data.errors);
                console.log(error);
            });
    }

    const createEmployee = (values, { resetForm, setErrors}) => {
        axios.post('/api/employees', values)
            .then((response) => {
                // employees.value.data.unshift(response.data);
                $('#employeeFormModal').modal('hide');
                resetForm();
                toastr.success('Employee added successfully');
                getEmployees()
            })
    };

    const handleSubmit = (values, actions) => {
        console.log(actions);
        if(editing.value){
            updateUser(values, actions);
        }else{
            createUser(values, actions);
        }
    }

    const searchQuery = ref(null);

    const selectedUsers = ref([]);

    const toggleSelection = (user) => {
        const index = selectedUsers.value.indexOf(user.id);
        if(index === -1) {
            selectedUsers.value.push(user.id);
        }else{
            selectedUsers.value.splice(index, 1);
        }
        console.log(selectedUsers.value);
    }
    const userIdBeingDeleted = ref(null);

    const confirmUserDeletion = (id) => {
        userIdBeingDeleted.value = id;
        $('#deleteUserModal').modal('show');
    };

    const deleteUser = () => {
        axios.delete(`/api/users/${userIdBeingDeleted.value}`)
            .then(() => {
                $('#deleteUserModal').modal('hide');
                toastr.success('User deleted successfully');
                users.value.data = users.value.data.filter(user => user.id !== userIdBeingDeleted.value);
            });
    };
    const bulkDelete = () => {
        axios.delete('/api/users', {
            data: {
                ids: selectedUsers.value
            }
        })
        .then(response => {
            users.value.data = users.value.data.filter(user => !selectedUsers.value.includes(user.id));
            selectedUsers.value = [];
            selectAll.value = false;
            toastr.success(response.data.message);
        });
    };

    const selectAll = ref(false);

    const selectAllUsers = () => {
        if(selectAll.value){
            selectedUsers.value = users.value.data.map(user => user.id);
        }else{
            selectedUsers.value = [];
        }
    }

    watch(searchQuery, debounce(() => {
        getUsers();
    },300));

    onMounted(() => {
        getUsers();
        getEmployees();
    });

</script>
<template>
    <div class="content-header">
               <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Users</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Users</li>
                            </ol>
                        </div>
                    </div>
               </div>
           </div>

           <div class="content">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex">
                            <button type="button" class="mb-2 btn btn-primary mr-2" @click="addUser">
                                <i class="fa fa-plus-circle mr-1"></i> Add New User
                            </button>
                            <button type="button" class="mb-2 btn btn-primary" @click="addEmployee">
                                <i class="fa fa-plus-circle mr-1"></i> Add New Employee
                            </button>
                            <div v-if="selectedUsers.length > 0">
                                <button type="button" class="ml-2 mb-2 btn btn-danger" @click="bulkDelete">
                                    <i class="fa fa-trash mr-1"></i> Delete Selected
                                </button>
                                <span class="ml-2">Selected {{  selectedUsers.length }} users</span>
                            </div>
                        </div>
                        <div>
                            <input type="text" v-model="searchQuery" class="form-control" placeholder="Search"/>
                            
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" v-model="selectAll" @change="selectAllUsers" /></th>
                                    <th style="width:10px">#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Registered Date</th>
                                    <th>Role</th>
                                    <th>Options</th>
                                </tr>
                            </thead>
                            <tbody v-if="users.data.length > 0">
                                <UserListItem v-for="(user, index) in users.data" 
                                    :key="user.id"
                                    :user=user
                                    :index=index
                                    @edit-user="editUser"
                                    @confirm-user-deletion="confirmUserDeletion"
                                    @toggle-selection="toggleSelection"
                                    :select-all="selectAll"                            
                                />
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="6" class="text-center">No results found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Bootstrap4Pagination :data="users" @pagination-change-page="getUsers" />
                </div>
           </div>

           <div class="modal fade" id="employeeFormModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span v-if="editing">Edit Employee</span>
                            <span v-else>Add New Employee</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>

                    <Form ref="form" @submit="createEmployee" v-slot="{ errors }">
                      <div class="modal-body">
                            
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Employee Name</label>
                                <Field name="emp_name" type="text" class="form-control" :class="{'is-invalid':errors.emp_name}" id="emp_name" />
                                <!-- <span class="invalid-feedback">{{ errors.emp_name }}</span> -->
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Position/Designation</label>
                                <Field name="emp_position" type="text" class="form-control" :class="{'is-invalid':errors.emp_position}" id="emp_position" />
                                <!-- <span class="invalid-feedback">{{ errors.emp_position }}</span> -->
                            </div>
                      </div>                    
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                      </div>
                    </Form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="userFormModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span v-if="editing">Edit User</span>
                            <span v-else>Add New User</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>

                    <Form ref="form" @submit="handleSubmit" :validation-schema="editing ? editUserSchema : createUserSchema" v-slot="{ errors }" :initial-values="editing ? formValues : form">
                      <div class="modal-body">
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Employee Name</label>
                                <Field name="employee" as="select"  class="form-control" :class="{'is-invalid':errors.employee}" id="employee">
                                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.emp_name }}</option>
                                </Field>
                                <span class="invalid-feedback">{{ errors.employee }}</span>
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlInput1">User Name</label>
                                <Field name="name" type="text" class="form-control" :class="{'is-invalid':errors.name}" id="name" />
                                <span class="invalid-feedback">{{ errors.name }}</span>
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Email address</label>
                                <Field name="email" type="email" class="form-control" :class="{'is-invalid':errors.email}" id="email" />
                                <span class="invalid-feedback">{{ errors.email }}</span>
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlInput1">Password</label>
                                <Field name="password" type="password" class="form-control" :class="{'is-invalid':errors.password}" id="password" />
                                <span class="invalid-feedback">{{ errors.password }}</span>
                            </div>
                      </div>                    
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                      </div>
                    </Form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <span>Delete User</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        </div>

                    <div class="modal-body">
                        <h5>Are you sure you want to delete this user?</h5>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button @click.prevent="deleteUser" type="button" class="btn btn-primary">Delete</button>
                    </div>
                    </div>
                </div>
            </div>
</template>