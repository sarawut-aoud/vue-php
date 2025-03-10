<template>
  <v-app-bar>
    <v-app-bar-title style="margin-inline-start: 0 !important">
      <Router-custom :theme="themes" :path="'/'">
        <template #default>
          <div class="d-flex ga-3 align-center">
            <div style="width: 100px">
              <v-img :src="'/api/assets/images/logo_1.jpg'" class="w-100"></v-img>
            </div>
            <div class="" style="width: fit-content">Reread</div>
          </div>
        </template>
      </Router-custom>
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
              <v-list-item
                v-bind="props"
                :prepend-avatar="item_info.avatar"
                :subtitle="item_info?.name?.nickname"
                :title="item_info?.name?.fullname"
                rounded="lg"
              >
              </v-list-item>
            </v-list>
          </template>
          <v-card min-width="250" rounded="lg">
            <v-list>
              <div class="d-flex flex-column ga-2 w-100">
                <v-list-item v-if="!!globalitem || globalitem.n != 'admin'">
                  <Router-custom :theme="themes" :path="'/users/info'">
                    <template #default>
                      <v-btn
                        variant="outlined"
                        class="d-flex justify-start w-100"
                        rounded="lg"
                        prepend-icon="mdi-account-circle"
                        >ข้อมูลส่วนตัว</v-btn
                      >
                    </template>
                  </Router-custom>
                </v-list-item>

                <v-list-item v-if="!!globalitem || globalitem.n != 'admin'">
                  <v-btn
                    variant="outlined"
                    class="d-flex justify-start w-100"
                    rounded="lg"
                    prepend-icon="mdi-clipboard-text-clock"
                    >ประวัติการสั่งซื้อ</v-btn
                  >
                </v-list-item>
                <v-list-item v-if="!!globalitem && globalitem.n == 'admin'">
                  <Router-custom :theme="themes" :path="'/admin'">
                    <template #default>
                      <v-btn
                        variant="outlined"
                        class="d-flex justify-start w-100"
                        rounded="lg"
                        prepend-icon="mdi-shield-crown"
                        >ระบบจัดการ</v-btn
                      >
                    </template>
                  </Router-custom>
                </v-list-item>
                <v-list-item>
                  <v-btn
                    @click="logout"
                    variant="outlined"
                    color="red"
                    class="d-flex justify-start w-100"
                    rounded="lg"
                    prepend-icon=" mdi-logout"
                    >ออกจากระบบ</v-btn
                  >
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
      <template v-if="!globalitem || globalitem.n != 'admin'">
        <v-divider vertical></v-divider>
        <div>
          <v-btn @click="carts = !carts" stacked flat>
            <template v-if="cartCount > 0">
              <v-badge :content="cartCount" color="red">
                <v-icon icon="mdi-cart" size="large"></v-icon>
              </v-badge>
            </template>
            <template v-else><v-icon icon="mdi-cart" size="large"></v-icon></template>
          </v-btn>
        </div>
      </template>
    </div>
  </v-app-bar>
  <Carts-items :carts="carts"></Carts-items>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<script setup>
import { ref, inject, watch ,onMounted ,provide } from "vue";
import CartsItems from "@/components/CartsItems.vue";
import { useLocalStorage } from "@/composables/useLocalStorage";
import { useCookie } from "@/composables/useCookie";
import { Notivue, Notification, push } from "notivue";
import api from "/utils/axios";

const emit = defineEmits(["themes"]);
const { deleteItem, isItem } = useLocalStorage();
const { deleteCookie, getCookie } = useCookie();
const item_info = inject("_info");
const themes = ref("light");
let temp = isItem("userToken") ? true : false;

import { useJWT } from "@/composables/useJWT";
const { getItem } = useJWT();
const globalitem = ref(getItem(getCookie("jwt")));
const is_login = ref(temp);
const carts = ref(false);

const logout = async () => {
  push.success({
    position: "top-right",
    title: "Logout !!",
    message: "logout สำเร็จกรุณารอสักครู่",
  });
  deleteItem("userToken");
  deleteCookie("jwt");
  setTimeout(() => {
    window.location.href = "";
  }, 1000);
};
watch(() => {
  if (!globalitem.value?.ui) {
    deleteItem("userToken");
    deleteCookie("jwt");
  }
  
});

const handleClick = () => {
  if (!themes.value) themes.value = "light";
  emit("themes", themes.value); // 🚀 ส่งข้อมูลไปให้ Parent
};
const cartCount = ref(0);
const getCountCart = async () => {
  let { data } = await api.get("/api/orders/getMyCart", {
    params: {
      uid: globalitem.value?.ui,
    },
  });
  if (data.data?.length > 0) {
    cartCount.value = data.data?.length;
  }else{
    cartCount.value =0
  }
};
onMounted(()=>{
  getCountCart()
  setInterval(() => {
    getCountCart()
  }, 1000);
});
</script>
