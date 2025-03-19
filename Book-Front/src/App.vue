<template>
  <v-app>
    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>
<script setup>
import { ref, provide, onMounted } from "vue";
import { useJWT } from "./composables/useJWT";
import { useCookie } from "./composables/useCookie";
import api from "/utils/axios";
const { getCookie } = useCookie();
const { getItem } = useJWT();
const globalitem = ref(getItem(getCookie("jwt")));
const info = ref("");
provide("_info", info);
const getInfo = async () => {
  if (!globalitem.value) return false;
  let { data } = await api({
    method: "get",
    url: "/api/Personal/getInfo",
    params: {
      uid: globalitem.value?.ui,
    },
  });
  if (data?.data) {
    info.value = data.data;
  }
};
const cartCount = ref(0);
const getCountCart = async () => {
  cartCount.value = 0;
  if (globalitem.value?.ui) {
    let { data } = await api.get("/api/orders/getMyCart", {
      params: {
        uid: globalitem.value?.ui,
      },
    });
    let noti = 0;
    let text = "";
    if (data.data?.length > 0) {
      cartCount.value = data.data?.length;
      noti = data.data?.length;
      if (noti > 0) {
        text = `(${noti})`;
      }
    } else {
      cartCount.value = 0;
    }
    if (globalitem.value.n == "emp") {
      document.getElementsByTagName("title")[0].innerHTML = `${text} ReRead`;
    }
  }
};
const listsCount = ref(0);
const getHistory = async () => {
  listsCount.value = 0;
  if (globalitem.value.n == "emp") return;
  await api
    .get("/api/orders/getHistorys")
    .then((rs) => {
      return rs.data;
    })
    .then((result) => {
      if (result?.data.length > 0) {
     
        listsCount.value = result.data.filter((e) => e.status == "paid").length;
      }
    })
    .catch((e) => {
      console.error(e);
    });
};
provide("_getInfo", getInfo);
provide("getCountCart", getCountCart);
provide("cartCount", cartCount);
provide("listsCount", listsCount);
provide("getlistsCount", getHistory);

onMounted(() => {
  getInfo();
  getCountCart();
  getHistory();
});
</script>

<style>
@import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap");

* {
  font-family: "Noto Sans Thai", serif;
  font-optical-sizing: auto;
  font-weight: 500;
  font-style: normal;
}

[aria-label="Notifications"] {
  z-index: 3000 !important;
  position: relative;
  /* หรือ absolute/fixed ถ้าจำเป็น */
}
body .v-main {
  background: #daad76 !important;
}
.swal2-container {
  z-index: 9999999999999999;
}
</style>
