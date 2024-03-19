<script setup>
import axios from 'axios';
import { ref, onMounted, reactive } from 'vue';

const selectedDocumentStatus = ref('all');
const totalDocumentsCount = ref(0);

const selectedDocumentType = ref('all');
const totalOverdueDocumentsCount = ref(0);

const getDocumentsCount = () => {
    axios.get('/api/stats/documents', {
        params: {
            status: selectedDocumentStatus.value,
        }
    })
    .then((response) => {
        totalDocumentsCount.value = response.data.totalDocumentsCount;
    })
}

const getOverdueDocumentsCount = () => {
    axios.get('/api/overdue/documents', {
        params: {
            type: selectedDocumentType.value,
        }
    })
    .then((response) => {
        totalOverdueDocumentsCount.value = response.data.totalOverdueDocumentsCount;
    })
}

const selectedReferralNearDueType = ref('referrals');
const nearDueReferrals = ref({'data': []});
const totalReferralNearDueCount = ref(0);

const getReferralNearDueCount = () => {
    const selectedReferralType = selectedReferralNearDueType.value;
    axios.get('/api/stats/filtered_documents', {
        params: {
            doc_type: selectedReferralType,
        }
    })
    .then((response) => {
        nearDueReferrals.value.data = response.data;
        if(selectedReferralType === 'referrals'){
            nearDueReferrals.value.data = nearDueReferrals.value.data.filter(referral => 
                (referral.type === 8 && (referral.days_active >= 1 && referral.days_active <= 3)) ||
                ((referral.type === 1 || referral.type === 2 || referral.type === 4) && (referral.days_active >= 4 && referral.days_active <= 7)) ||
                (referral.type === 3 && (referral.days_active >= 7 && referral.days_active <=10))
            )
        }else if(selectedReferralType === 'admin_docs'){
            nearDueReferrals.value.data = nearDueReferrals.value.data.filter(referral =>
                (referral.days_active >= 1 && referral.days_active <= 3)
            )
        }else if(selectedReferralType === 'municipal' || selectedReferralType === 'provincial' || selectedReferralType === 'other_referral'){
            nearDueReferrals.value.data = nearDueReferrals.value.data.filter(referral =>
                (referral.days_active >= 4 && referral.days_active <= 7)
            )
        }else if(selectedReferralType === 'code'){
            nearDueReferrals.value.data = nearDueReferrals.value.data.filter(referral =>
                (referral.days_active >= 7 && referral.days_active <= 10)
            )
        }
        totalReferralNearDueCount.value = nearDueReferrals.value.data.length;
    })
}

const selectedReferralPastDueType = ref('referrals');
const pastDueReferrals = ref({'data': []});
const totalReferralPastDueCount = ref(0);

const getReferralPastDueCount = () => {
    const selectedReferralType = selectedReferralPastDueType.value;
    axios.get('/api/stats/filtered_documents', {
        params: {
            doc_type: selectedReferralType,
        }
    })
    .then((response) => {
        pastDueReferrals.value.data = response.data;
        if(selectedReferralType === 'referrals'){
            pastDueReferrals.value.data = pastDueReferrals.value.data.filter(referral => 
                (referral.type === 8 && referral.days_active > 3) ||
                (referral.type === 1 && referral.days_active > 7) ||
                (referral.type === 2 && referral.days_active > 7) ||
                (referral.type === 3 && referral.days_active > 10) ||
                (referral.type === 4 && referral.days_active > 7) 
            )
        }else if(selectedReferralType === 'admin_docs'){
            pastDueReferrals.value.data = pastDueReferrals.value.data.filter(referral =>
                referral.days_active > 3    
            )
        }else if(selectedReferralType === 'municipal' || selectedReferralType === 'municipal' || selectedReferralType === 'other_referral'){
            pastDueReferrals.value.data = pastDueReferrals.value.data.filter(referral =>
                referral.days_active > 7
            )
        }else if(selectedReferralType === 'code'){
            pastDueReferrals.value.data = pastDueReferrals.value.data.filter(referral =>
                referral.days_active > 10
            )
        }
        totalReferralPastDueCount.value = pastDueReferrals.value.data.length;
    })
}

const selectedCaseNearDueType = ref('cases');
const nearDueCases = ref({'data': []});
const totalCaseNearDueCount = ref(0);

const getCaseNearDueCount = () => {
    const selectedCaseType = selectedCaseNearDueType.value;
    axios.get('/api/stats/filtered_documents', {
        params: {
            doc_type: selectedCaseType,
        }
    })
    .then((response) => {
        nearDueCases.value.data = response.data;
        nearDueCases.value.data = nearDueCases.value.data.filter(court_case =>
            court_case.days_active >= 12 && court_case.days_active <= 15
        )        
        totalCaseNearDueCount.value = nearDueCases.value.data.length;
    })
}

const selectedCasePastDueType = ref('cases');
const pastDueCases = ref({'data': []});
const totalCasePastDueCount = ref(0);

const getCasePastDueCount = () => {
    const selectedCaseType = selectedCasePastDueType.value;
    axios.get('/api/stats/filtered_documents', {
        params: {
            doc_type: selectedCaseType,
        }
    })
    .then((response) => {
        pastDueCases.value.data = response.data;
        pastDueCases.value.data = pastDueCases.value.data.filter(court_case =>
            court_case.days_active > 15
        )
        totalCasePastDueCount.value = pastDueCases.value.data.length;
    })
}

const totalToReceiveCount = ref(0);
const totalToReleaseCount = ref(0);

const getTotalToDoCount = () => {
    axios.get('/api/stats/to-do')
        .then((response) => {
            totalToReceiveCount.value = response.data.totalToReceiveCount;
            totalToReleaseCount.value = response.data.totalToReleaseCount;
        })
}

const selectedDateRange = ref('today');
const totalUsersCount = ref(0);

const getUsersCount = () => {
    axios.get('/api/stats/users', {
        params: {
            date_range: selectedDateRange.value,
        }
    })
    .then((response) => {
        totalUsersCount.value = response.data.totalUsersCount;
    })
};

const currentUser = reactive({});

const getUser = () => {
    axios.get('/api/users/getUser')
        .then((response) => {
            currentUser.name = response.data.name;
            currentUser.id = response.data.id;
    })
}

const selectedReferralTypeTotal = ref('referrals');
const selectedCaseTypeTotal = ref('cases');
const totalReferrals = ref(0);
const totalCases = ref(0);
const getTotals = () => {
    axios.get('/api/dashboard/stats/totals', {
        params: {
            ref_doc_type: selectedReferralTypeTotal.value,
            case_doc_type: selectedCaseTypeTotal.value,
        }
    })
        .then((response) => {
            totalReferrals.value = response.data.totalReferrals;
            totalCases.value = response.data.totalCases;
        })
}

onMounted(() => {
    getOverdueDocumentsCount();
    getDocumentsCount();
    getUsersCount();
    getUser();
    getReferralNearDueCount();
    getCaseNearDueCount();
    getReferralPastDueCount();
    getCasePastDueCount();
    getTotalToDoCount();
    getTotals();
})

</script>

<template>
     <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                        <div class="col-sm-6">
                            <!-- <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Starter Page</li>
                            </ol> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="jumbotron">
                                    <p class="lead">To Do</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3>{{ totalToReceiveCount }}</h3>                                                        
                                                    </div>
                                                    <p>To Receive</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalToReceiveCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            query_type: 'active',
                                                            to_do: 'to-receive'
                                                        }
                                                    }">
                                                    View
                                                </router-link>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3>{{ totalToReleaseCount }}</h3>
                                                    </div>
                                                    <p>To Release</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                
                                                <router-link class="small-box-footer" v-if="totalToReleaseCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            query_type: 'active',
                                                            to_do: 'to-release',
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
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="jumbotron">
                                    <p class="lead">Near Due Documents</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalReferralNearDueCount }}</h3>
                                                        <select v-model="selectedReferralNearDueType" @change="getReferralNearDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="referrals" selected>All</option>
                                                            <option value="code">Code</option>
                                                            <option value="municipal">Municipal</option>
                                                            <option value="provincial">Provincial</option>
                                                            <option value="other_referral">Other Referrals</option>
                                                            <option value="admin_docs">Admin Docs</option>
                                                        </select>
                                                    </div>
                                                    <p>Referrals</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalReferralNearDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            query_type: 'active',
                                                            doc_type: selectedReferralNearDueType,
                                                            status: 'near_due'
                                                        }
                                                    }">
                                                    View
                                                </router-link>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalCaseNearDueCount }}</h3>
                                                        <select v-model="selectedCaseNearDueType" @change="getCaseNearDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
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
                                                
                                                <router-link class="small-box-footer" v-if="totalCaseNearDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            doc_type: selectedCaseNearDueType,
                                                            status: 'near_due'
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
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="jumbotron">
                                    <p class="lead">Past Due Documents</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalReferralPastDueCount }}</h3>
                                                        <select v-model="selectedReferralPastDueType" @change="getReferralPastDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="referrals">All</option>
                                                            <option value="code">Code</option>
                                                            <option value="municipal">Municipal</option>
                                                            <option value="provincial">Provincial</option>
                                                            <option value="other_referral">Other Referrals</option>
                                                            <option value="admin_docs">Admin Docs</option>
                                                        </select>
                                                    </div>
                                                    <p>Referrals</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalReferralPastDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            query_type: 'active',
                                                            doc_type: selectedReferralPastDueType,
                                                            status: 'past_due'
                                                        }
                                                    }">
                                                    View
                                                </router-link>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalCasePastDueCount }}</h3>
                                                        <select v-model="selectedCasePastDueType" @change="getCasePastDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="cases">All</option>
                                                            <option value="administrative">Administrative</option>
                                                            <option value="judicial">Judicial</option>
                                                            <option value="quasi">Quasi Judicial</option>
                                                        </select>
                                                    </div>
                                                    <p>Court Cases</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalCasePastDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            query_type: 'active',
                                                            doc_type: selectedCasePastDueType,
                                                            status: 'past_due'
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

                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="jumbotron">
                                    <p class="lead">Totals</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalReferrals }}</h3>
                                                        <select v-model="selectedReferralTypeTotal" @change="getTotals()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="referrals" selected>All</option>
                                                            <option value="code">Code</option>
                                                            <option value="municipal">Municipal</option>
                                                            <option value="provincial">Provincial</option>
                                                            <option value="other_referral">Other Referrals</option>
                                                            <option value="admin_docs">Admin Docs</option>
                                                        </select>                                                        
                                                    </div>
                                                    <p>Total Referrals</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalReferrals > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            query_type: 'all',
                                                            doc_type: 'referrals'
                                                        }
                                                    }">
                                                    View
                                                </router-link>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalCases }}</h3>
                                                        <select v-model="selectedCaseTypeTotal" @change="getTotals()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="cases" selected>All</option>
                                                            <option value="administrative">Administrative</option>
                                                            <option value="judicial">Municipal</option>
                                                            <option value="quasi">Provincial</option>
                                                        </select>
                                                    </div>
                                                    <p>Total Cases</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                
                                                <router-link class="small-box-footer" v-if="totalCases > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            query_type: 'all',
                                                            doc_type: 'cases',
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
                    <!-- <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="jumbotron">
                                    <p class="lead">Near Due Documents</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalReferralNearDueCount }}</h3>
                                                        <select v-model="selectedReferralNearDueType" @change="getReferralNearDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="referrals" selected>All</option>
                                                            <option value="code">Code</option>
                                                            <option value="municipal">Municipal</option>
                                                            <option value="provincial">Provincial</option>
                                                            <option value="other_referral">Other Referrals</option>
                                                            <option value="admin_docs">Admin Docs</option>
                                                        </select>
                                                    </div>
                                                    <p>Referrals</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalReferralNearDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            doc_type: selectedReferralNearDueType,
                                                            status: 'near_due'
                                                        }
                                                    }">
                                                    View
                                                </router-link>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalCaseNearDueCount }}</h3>
                                                        <select v-model="selectedCaseNearDueType" @change="getCaseNearDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
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
                                                
                                                <router-link class="small-box-footer" v-if="totalCaseNearDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            doc_type: selectedCaseNearDueType,
                                                            status: 'near_due'
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
                    </div> -->
                    
                    <!-- <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="jumbotron">
                                    <p class="lead">Past Due Documents</p>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalReferralPastDueCount }}</h3>
                                                        <select v-model="selectedReferralPastDueType" @change="getReferralPastDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="referrals">All</option>
                                                            <option value="code">Code</option>
                                                            <option value="municipal">Municipal</option>
                                                            <option value="provincial">Provincial</option>
                                                            <option value="other_referral">Other Referrals</option>
                                                            <option value="admin_docs">Admin Docs</option>
                                                        </select>
                                                    </div>
                                                    <p>Referrals</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalReferralPastDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            doc_type: selectedReferralPastDueType,
                                                            status: 'past_due'
                                                        }
                                                    }">
                                                    View
                                                </router-link>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <div class="d-flex justify-content-between">
                                                        <h3 class="mr-3">{{ totalCasePastDueCount }}</h3>
                                                        <select v-model="selectedCasePastDueType" @change="getCasePastDueCount()"
                                                            style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0 form-control">
                                                            <option value="cases">All</option>
                                                            <option value="administrative">Administrative</option>
                                                            <option value="judicial">Judicial</option>
                                                            <option value="quasi">Quasi Judicial</option>
                                                        </select>
                                                    </div>
                                                    <p>Court Cases</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="ion ion-bag"></i>
                                                </div>
                                                <router-link class="small-box-footer" v-if="totalCasePastDueCount > 0"
                                                    :to="{
                                                        name: 'admin.documents',
                                                        query: {
                                                            doc_type: selectedCasePastDueType,
                                                            status: 'past_due'
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
                    </div>                     -->
                </div>

                <!-- <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <div class="d-flex justify-content-between">
                                    <h3>{{ totalUsersCount }}</h3>
                                    <select v-model="selectedDateRange" @change="getUsersCount()"
                                        style="height: 2rem; outline: 2px solid transparent;" class="px-1 rounded border-0">
                                        <option value="today">Today</option>
                                        <option value="30_days">30 days</option>
                                        <option value="60_days">60 days</option>
                                        <option value="360_days">360 days</option>
                                        <option value="month_to_date">Month to Date</option>
                                        <option value="year_to_date">Year to Date</option>
                                    </select>
                                </div>
                                <p>Users</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-bag"></i>
                            </div>
                            <router-link to="/admin/users" class="small-box-footer">
                                View Users
                                <i class="fas fa-arrow-circle-right"></i>
                            </router-link>
                        </div>
                    </div>
                </div> -->
            </div> 
</template>