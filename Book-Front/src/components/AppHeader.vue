<template>
    <v-app-bar scroll-behavior="inverted" class="pa-2 position-fixed">
        <v-app-bar-title>
            <a href="/" class="text-decoration-none "
                :class="{ 'text-grey-darken-4': (!themes || themes == 'light'), 'text-white': themes == 'dark' }">Reread</a>
        </v-app-bar-title>
        <div class="me-2 d-flex ga-4 align-center">
            <template v-if="!is_login">
                <v-btn href="register" hide-details>สมัครสมาชิก</v-btn>
                <v-divider vertical></v-divider>
                <v-btn href="login" hide-details>เข้าสู่ระบบ</v-btn>
            </template>
            <template v-else>
                <v-menu :close-on-content-click="false" location="bottom">
                    <template v-slot:activator="{ props }">
                        <v-list>
                            <v-list-item v-bind="props" :prepend-avatar="item_info.avatar"
                                :subtitle="item_info?.name?.nickname" :title="item_info?.name?.fullname" rounded="lg">
                            </v-list-item>
                        </v-list>
                    </template>
                    <v-card min-width="250" rounded="lg">
                        <v-list>
                            <div class="d-flex flex-column ga-2 w-100">
                                <v-list-item>
                                    <v-btn href="users/info" variant="outlined" class="d-flex justify-start w-100"
                                        rounded="lg" prepend-icon="mdi-account-circle">ข้อมูลส่วนตัว</v-btn>
                                </v-list-item>

                                <v-list-item>
                                    <v-btn variant="outlined" class="d-flex justify-start w-100" rounded="lg"
                                        prepend-icon="mdi-clipboard-text-clock">ประวัติการสั่งซื้อ</v-btn>
                                </v-list-item>
                                <v-list-item>
                                    <v-btn @click="logout" variant="outlined" color="red"
                                        class="d-flex justify-start w-100" rounded="lg"
                                        prepend-icon=" mdi-logout">ออกจากระบบ</v-btn>
                                </v-list-item>
                                <div class="d-flex justify-center">
                                    <v-btn-toggle rounded="lg" v-model="themes" @click="handleClick">
                                        <v-btn border value="light" size="small">
                                            <v-icon>mdi-weather-sunny</v-icon>
                                        </v-btn>
                                        <v-btn border value="dark" size="small">
                                            <v-icon>mdi-weather-night</v-icon>
                                        </v-btn>
                                    </v-btn-toggle>
                                </div>
                            </div>
                        </v-list>
                    </v-card>
                </v-menu>
            </template>
            <v-divider vertical></v-divider>
            <div>
                <v-badge :content="5" color="red">
                    <v-btn border icon="mdi-cart" @click="carts = !carts"></v-btn>
                </v-badge>
            </div>
        </div>
    </v-app-bar>
    <Carts-items :carts="carts"></Carts-items>
    <Notivue v-slot="item">
        <Notification :item="item" />
    </Notivue>
</template>
<script setup>
import { ref,inject, } from 'vue'
import CartsItems from '@/components/CartsItems.vue';
import {useLocalStorage} from '@/composables/useLocalStorage';
import {useCookie} from '@/composables/useCookie';

import { Notivue, Notification, push } from 'notivue'

const emit = defineEmits(['themes']);
const {deleteItem,isItem} = useLocalStorage()
const item_info = inject('_info');
const themes = ref('light');
let temp = isItem('userToken')?true:false;
const {  deleteCookie } = useCookie();

const is_login = ref(temp)
const carts = ref(false)
const show = ref(false)
const items = ref([
        { text: 'Real-Time', icon: 'mdi-clock' },
        { text: 'Audience', icon: 'mdi-account' },
        { text: 'Conversions', icon: 'mdi-flag' },
      ])

const logout= async()=>{
    push.success({
            position: 'top-right',
            title:"Logout !!",
            message:'logout สำเร็จกรุณารอสักครู่'
        })
        deleteItem('userToken')
        deleteCookie('jwt')
        setTimeout(() => {
          window.location.href = ''
        }, 1000);
};
const handleClick = () => {
   if(!themes.value) themes.value = 'light'
  emit('themes', themes.value); // 🚀 ส่งข้อมูลไปให้ Parent
};
</script>