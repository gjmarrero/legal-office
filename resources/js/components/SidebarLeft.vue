<script setup>
    import { useAuthUserStore } from '../stores/AuthUserStore.js';
    import { useRouter } from 'vue-router';
    import { useSettingStore} from '../stores/SettingStore.js';

    const router = useRouter();

    const authUserStore = useAuthUserStore();

    const settingStore = useSettingStore();

    const logout = () => {
        axios.post('/logout')
            .then((response) => {
                authUserStore.user.name = '';
                router.push('/login');
            })
    }
</script>
<template>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        
        <router-link to="/admin/dashboard" class="brand-link">
            <img src="../../../public/favicon.ico"
                class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">{{ settingStore.setting.app_name }}</span>
        </router-link>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img :src="authUserStore.user.avatar"  alt="User Image" class="img-circle elevation-2">
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ authUserStore.user.name }}</a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <li class="nav-item">
                        <router-link to="/admin/dashboard" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>
                                Dashboard
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/documents" :class="$route.path.startsWith('/admin/documents') ? 'active' : ''" class="nav-link">
                            <font-awesome-icon icon="fa-book" class="nav-icon fas" />
                            <p>
                                Documents
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/outgoing" :class="$route.path.startsWith('/admin/outgoing') ? 'active' : ''" class="nav-link">
                            <font-awesome-icon icon="fa-book" class="nav-icon fas" />
                            <p>
                                Outgoing
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/users" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Users
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/clients" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Clients
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/settings" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>
                                Settings
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/admin/profile" active-class="active" class="nav-link">
                            <i class="nav-icon fas fa-user"></i>
                            <p>
                                Profile
                            </p>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <form class="nav-link" @click.prevent="logout">
                            <a href="#" >
                                <i class="nav-icon fas fa-signout-alt"></i>
                                <p>
                                    Logout
                                </p>
                            </a>
                        </form>                            
                    </li>
                </ul>
            </nav>

        </div>

    </aside>
</template>