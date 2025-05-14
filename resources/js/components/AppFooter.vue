<script setup>
import { useAuthUserStore } from '../stores/AuthUserStore.js';

    const authUserStore = useAuthUserStore();

    defineProps({
        settings: Object,
    });
</script>

<template>
    <footer class="main-footer">

        <div class="float-right d-none d-sm-inline">
            {{ authUserStore.user.name }}
        </div>

        <strong>&copy; PGO-MIS|Marrero G.</strong> 
    </footer>
</template>