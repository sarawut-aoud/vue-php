<template>
    <v-responsive class="border rounded ">
        <v-app :theme="theme" class="position-relative">
            <App-header @themes="getTheme"></App-header>
        </v-app>
    </v-responsive>
    <app-footer></app-footer>
</template>
<script setup>
import { ref } from 'vue';
import api from '/utils/axios';
const theme = ref('light');

const getTheme=(e)=>{
    theme.value = e
}


</script>