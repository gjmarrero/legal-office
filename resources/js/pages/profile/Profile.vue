<script setup>
import axios from 'axios';
import { ref, onMounted, reactive } from 'vue';
import { useToastr } from '@/toastr';
import { useAuthUserStore } from '../../stores/AuthUserStore.js';
import { useRoute, useRouter, onBeforeRouteUpdate } from 'vue-router';

const authUserStore = useAuthUserStore();

const toastr = useToastr();

const errors = ref();

const updateProfile = () => {
    errors.value = '';
    axios.put('/api/profile', {
        name: authUserStore.user.name,
        email: authUserStore.user.email,
        role: authUserStore.user.role,
    })
        .then((response) => {
            toastr.success('Profile updated successfully');
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
            }
        })

}

const changePasswordForm = reactive({
    currentPassword: '',
    password: '',
    passwordConfirmation: '',
});

const handleChangePassword = () => {
    axios.post('/api/change-user-password', changePasswordForm)
        .then((response) => {
            toastr.success(response.data.message);
            for (const field in changePasswordForm) {
                changePasswordForm[field] = '';
            }
        })
        .catch((error) => {
            if (error.response && error.response.status === 422) {
                errors.value = error.response.data.errors;
            }
        });
}

const fileInput = ref(null);

const openFileInput = () => {
    fileInput.value.click();
}

const handleFileChange = (event) => {
    const file = event.target.files[0];
    authUserStore.user.avatar = URL.createObjectURL(file);

    const formData = new FormData();

    formData.append('profile_picture', file);

    axios.post('/api/upload-profile-image', formData)
        .then((response) => {
            toastr.success('Image uploaded successfully');
        });
}

const totalCases = ref(0);
const selectedCaseType = ref('cases');
const getSelectedCaseTypeCount = () => {
    console.log(selectedCaseType.value);
    axios.get('/api/profile/employee_counter_cases', {
        params: {
            selectedCaseType: selectedCaseType.value,
        }
    })
        .then((response) => {
            totalCases.value = response.data.caseTypeCount;
        })
}

const totalReferrals = ref(0);
const selectedReferralType = ref('referrals');
const getSelectedReferralTypeCount = () => {
    console.log(selectedReferralType.value);
    axios.get('/api/profile/employee_counter_referrals', {
        params: {
            selectedReferralType: selectedReferralType.value,
        }
    })
    .then((response) => {
        totalReferrals.value = response.data.referralTypeCount;
        console.log(totalReferrals.value);
    })
}



const totalAdminDocs = ref(0);
const totalNotaries = ref(0);

const getEmployeeCounts = () => {
    axios.get('/api/profile/employee_counter', {
        params: {
            current_employee: authUserStore.user.employee_id,
        }
    })
        .then((response) => {
            //totalCases.value = response.data.totalCases;
            //totalReferrals.value = response.data.totalReferrals;
            totalAdminDocs.value = response.data.totalAdminDocs;
            totalNotaries.value = response.data.totalNotaries;
        })
}

onMounted(() => {
    getEmployeeCounts();
    getSelectedCaseTypeCount();
    getSelectedReferralTypeCount();
})
</script>

<template>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Profile</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="row">
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <input @change="handleFileChange" ref="fileInput" type="file" class="d-none">
                            <img @click="openFileInput" class="profile-user-img img-circle" :src="authUserStore.user.avatar"
                                alt="User profile picture">
                        </div>

                        <h3 class="profile-username text-center">{{ authUserStore.user.name }}</h3>

                        <p class="text-muted text-center">{{ authUserStore.user.role }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#profile" data-toggle="tab"><i
                                        class="fa fa-user mr-1"></i> Edit Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="#changePassword" data-toggle="tab"><i
                                        class="fa fa-key mr-1"></i> Change
                                    Password</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="profile">
                                <form @submit.prevent="updateProfile()" class="form-horizontal">
                                    <div class="form-group row">
                                        <label for="inputName" class="col-sm-2 col-form-label">Name</label>
                                        <div class="col-sm-10">
                                            <input v-model="authUserStore.user.name" type="text" class="form-control"
                                                id="inputName" placeholder="Name">
                                            <span class="text-danger text-sm" v-if="errors && errors.name">{{ errors.name[0]
                                            }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                                        <div class="col-sm-10">
                                            <input v-model="authUserStore.user.email" type="email" class="form-control "
                                                id="inputEmail" placeholder="Email">
                                            <span class="text-danger text-sm" v-if="errors && errors.email">{{
                                                errors.email[0] }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="offset-sm-2 col-sm-10">
                                            <button type="submit" class="btn btn-success"><i class="fa fa-save mr-1"></i>
                                                Save Changes</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane" id="changePassword">
                                <form @submit.prevent="handleChangePassword" class="form-horizontal">
                                    <div class="form-group row">
                                        <label for="currentPassword" class="col-sm-3 col-form-label">Current
                                            Password</label>
                                        <div class="col-sm-9">
                                            <input v-model="changePasswordForm.currentPassword" type="password"
                                                class="form-control " id="currentPassword" placeholder="Current Password">
                                            <span class="text-danger text-sm" v-if="errors && errors.currentPassword">{{
                                                errors.currentPassword[0] }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="newPassword" class="col-sm-3 col-form-label">New
                                            Password</label>
                                        <div class="col-sm-9">
                                            <input v-model="changePasswordForm.password" type="password"
                                                class="form-control " id="newPassword" placeholder="New Password">
                                            <span class="text-danger text-sm" v-if="errors && errors.password">{{
                                                errors.password[0] }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="passwordConfirmation" class="col-sm-3 col-form-label">Confirm
                                            New Password</label>
                                        <div class="col-sm-9">
                                            <input v-model="changePasswordForm.passwordConfirmation" type="password"
                                                class="form-control " id="passwordConfirmation"
                                                placeholder="Confirm New Password">
                                            <span class="text-danger text-sm"
                                                v-if="errors && errors.passwordConfirmation">{{
                                                    errors.passwordConfirmation[0] }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="offset-sm-3 col-sm-9">
                                            <button type="submit" class="btn btn-success"><i class="fa fa-save mr-1"></i>
                                                Save Changes</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="jumbotron">
                            <p class="lead">Counter</p>
                            <div class="row">
                                <div class="col">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <div class="d-flex justify-content-between">
                                                <h3 class="mr-3">{{ totalCases }}</h3>
                                                <select v-model="selectedCaseType" @change="getSelectedCaseTypeCount()"
                                                    style="height: 2rem; outline: 2px solid transparent;"
                                                    class="px-1 rounded border-0 form-control">
                                                    <option value="cases">All</option>
                                                    <option value="administrative">Administrative</option>
                                                    <option value="judicial">Judicial</option>
                                                    <option value="quasi">Quasi-Judicial</option>
                                                </select>
                                            </div>
                                            <p>Court Cases</p>
                                        </div>
                                        <div class="icon">
                                            <i class="ion ion-bag"></i>
                                        </div>

                                        <router-link class="small-box-footer" v-if="totalCases > 0" :to="{
                                            name: 'admin.documents',
                                            query: {
                                                query_type: 'all',
                                                doc_type: selectedCaseType
                                            }
                                        }">
                                            View
                                        </router-link>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <div class="d-flex justify-content-between">
                                                <h3 class="mr-3">{{ totalReferrals }}</h3>
                                                <select v-model="selectedReferralType" @change="getSelectedReferralTypeCount()"
                                                    style="height: 2rem; outline: 2px solid transparent;"
                                                    class="px-1 rounded border-0 form-control">
                                                    <option value="referrals">All</option>
                                                    <option value="municipal">Municipal Ordinance</option>
                                                    <option value="provincial">Provincial Ordinance</option>
                                                    <option value="other">Other Referrals</option>
                                                    <option value="admin_docs">Admin Documents</option>
                                                    <option value="code">Code</option>
                                                </select>
                                            </div>
                                            <p>Referrals</p>
                                        </div>
                                        <div class="icon">
                                            <i class="ion ion-bag"></i>
                                        </div>

                                        <router-link class="small-box-footer" v-if="totalReferrals > 0" :to="{
                                            name: 'admin.documents',
                                            query: {
                                                query_type: 'all',
                                                doc_type: selectedReferralType
                                            }
                                        }">
                                            View
                                        </router-link>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <div class="d-flex justify-content-between">
                                                <h3 class="mr-3">{{ totalNotaries }}</h3>
                                            </div>
                                            <p>Notaries</p>
                                        </div>
                                        <div class="icon">
                                            <i class="ion ion-bag"></i>
                                        </div>

                                        <router-link class="small-box-footer" v-if="totalNotaries > 0" :to="{
                                            name: 'admin.documents',
                                            query: {
                                                query_type: 'all',
                                                doc_type: 'notary'
                                            }
                                        }">
                                            View
                                        </router-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.profile-user-img:hover {
    background-color: blue;
    cursor: pointer;
}
</style>