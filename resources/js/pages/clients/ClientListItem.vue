<script setup>
    import { ref } from 'vue';
    import {useToastr} from '../../toastr.js';
    import axios from 'axios';

    const toastr = useToastr();

    const props = defineProps({
        client: Object,
        index: Number,
        selectAll: Boolean,
    });

    const emit = defineEmits(['clientDeleted', 'editClient', 'confirmClientDeletion']);

    // const roles = ref([
    //     {
    //         name:  'ADMIN',
    //         value: 1
    //     },
    //     {
    //         name: 'USER',
    //         value: 2,
    //     }
       
    // ]);

    // const changeRole = (user, role) => {
    //     axios.patch(`/api/users/${user.id}/change-role`, {
    //         role: role,
    //     })
    //     .then(() => {
    //         toastr.success('Role changed successfully');
    //     })
    // };

    const toggleSelection = () => {
        emit('toggleSelection', props.client);
    }
</script>

<template>
    <tr>
        <td><input type="checkbox" :checked="selectAll" @change="toggleSelection"/></td>
        <td>{{ index + 1 }}</td>
        <td>{{ client.name }}</td>
        <td>{{ client.office }}</td>
        <!-- <td>
            <select class="form-control" @change="changeRole(user, $event.target.value)">
                <option v-for="role in roles" :value="role.value" :selected="user.role == role.name">{{ role.name }}</option>
            </select>
        </td> -->
        <td>
            <a href="#" @click.prevent="$emit('editClient', client)"><i class="fa fa-edit"></i></a>
            <a href="#" @click.prevent="$emit('confirmClientDeletion', client.id)"><i class="fa fa-trash text-danger ml-2"></i></a> 
        </td>
    </tr>

    
</template>