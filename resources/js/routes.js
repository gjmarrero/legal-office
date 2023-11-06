import Dashboard from './components/Dashboard.vue';
import Documents from './pages/documents/ListDocuments.vue';
import DocumentForm from './pages/documents/DocumentForm.vue';
import UserList from './pages/users/UserList.vue';
import ClientList from './pages/clients/ClientList.vue';
import Settings from './pages/settings/Settings.vue';
import Profile from './pages/profile/Profile.vue';
import Login from './pages/auth/Login.vue';
import Transactions from './pages/documents/ListTransactions.vue';
import ListOutgoing from './pages/outgoing/ListOutgoing.vue';
import OutgoingForm from './pages/outgoing/Outgoing.vue';

export default[
    {
        path: '/login',
        name: 'admin.login',
        component: Login,
    },
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: Dashboard,
    },
    {
        path: '/admin/documents',
        name: 'admin.documents',
        component: Documents,
    },
    {
        path: '/admin/outgoing',
        name: 'admin.outgoing',
        component: ListOutgoing,
    },
    {
        path: '/admin/outgoing/create',
        name: 'admin.outgoing.create',
        component: OutgoingForm,
    },
    {
        path: '/admin/documents/:doc_status',
        name: 'admin.documents.filtered',
        component: Documents
    },
    {
        path: '/admin/users',
        name: 'admin.users',
        component: UserList
    },
    {
        path: '/admin/clients',
        name: 'admin.clients',
        component: ClientList
    },
    {
        path: '/admin/settings',
        name: 'admin.settings',
        component: Settings
    },
    {
        path: '/admin/profile',
        name: 'admin.profile',
        component: Profile
    },
    {
        path:'/admin/documents/create',
        name: 'admin.documents.create',
        component: DocumentForm,
    },
    {
        path:'/admin/documents/:id/edit',
        name: 'admin.documents.edit',
        component: DocumentForm,
    },
    {
        path:'/admin/documents/transactions/:id',
        name: 'admin.documents.transactions',
        component: Transactions,
    },
    {
        path:'/admin/documents/overdue/:doc_type',
        name: 'admin.documents.overdue',
        component: Documents
    },
    {
        path:'/admin/documents/all/:doc_status',
        name: 'admin.documents.all',
        component: Documents
    }
]