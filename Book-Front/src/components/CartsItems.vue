<template>
    <v-navigation-drawer width="500" v-model="carts" location="end" temporary class="pa-3">
        <v-list lines="two" class="mt-5 ga-2 d-flex flex-column">
            <v-list-item rounded="lg" border v-for="item in items" :key="item">
                <v-list-title>{{ item.text }}</v-list-title>
            </v-list-item>
        </v-list>
        <template v-slot:append>
            <div class="pa-2">
                <v-btn color="success" prepend-icon="mdi-cash" block>ชำระเงิน</v-btn>

            </div>
        </template>
    </v-navigation-drawer>
</template>
<script>
import {defineComponent} from 'vue';

export default defineComponent({
    props:{
        carts:{
            default:false,
            type:Boolean
        }
    },
    data(){
        return{
            items: [
                { text: 'Real-Time', icon: 'mdi-clock' },
                { text: 'Audience', icon: 'mdi-account' },
                { text: 'Conversions', icon: 'mdi-flag' },
            ],
        }
    },
    mounted(){
        console.log(this.carts);
    }
})
</script>
