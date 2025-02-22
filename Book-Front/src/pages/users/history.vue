<template>
    <v-responsive class="border rounded ">
        <v-app :theme="theme" class="position-relative">
            <App-header></App-header>
        </v-app>
    </v-responsive>
    <app-footer></app-footer>
</template>
<script setup>
import {ref} from 'vue'
import api from '/utils/axios';
import { ref } from 'vue';
const theme =ref('light')
</script>