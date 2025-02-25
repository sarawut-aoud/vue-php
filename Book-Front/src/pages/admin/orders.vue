<template>
    <v-card title="ออเดอร์" solo border rounded="lg">
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
            sortable: false,
            title: 'Dessert (100g serving)',
          },
          { key: 'calories', title: 'Calories' },
          { key: 'fat', title: 'Fat (g)' },
          { key: 'carbs', title: 'Carbs (g)' },
          { key: 'protein', title: 'Protein (g)' },
          { key: 'iron', title: 'Iron (%)' },
        ]);

</script>