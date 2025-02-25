<template>
    <v-card title="หมวดหมู่หนังสือ" solo border rounded="lg">
        <v-card-text>
            <template v-slot:text>
                <v-text-field v-model="search" label="Search" prepend-inner-icon="mdi-magnify" variant="outlined"
                    hide-details single-line></v-text-field>
            </template>

            <v-data-table :headers="headers" :items="[]" :search="search"></v-data-table>
        </v-card-text>
    </v-card>
</template>
<script setup>
import {ref} from 'vue';


const search = ref('');
const  headers = ref([
          {
            align: 'start',
            key: 'name',
            title: 'ชื่อหมวดหมู่',
          },
          { key: 'cate_no', title: 'รหัสหมวดหมู่' },
          { key: 'manage', title: '#' },
        ]);

</script>