<template>
  <v-navigation-drawer
    width="500"
    v-model="props.carts"
    location="end"
    temporary
    class="pa-3"
  >
    <v-list lines="two" class="mt-5 ga-2 d-flex flex-column">
      <v-list-item rounded="lg" border v-for="item in myorders" :key="item">
        <v-list-title>ชื่อสินค้า : {{ item.product?.name }}</v-list-title>
        <v-list-item>
          <div class="pa-2">
            <div class="mb-3 d-flex justify-start w-100 align-end ga-3">
              <v-window show-arrows>
                <v-window-item v-for="n in item?.product?.picture">
                  <v-img :src="n.path" height="100" width="100" cover></v-img>
                </v-window-item>
              </v-window>
              <div class="d-flex flex-column ga-2 w-100">
                <v-text-field
                  label="จำนวน"
                  v-model="item.amount"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details=""
                ></v-text-field>
                <div class="d-flex align-center ga-2 w-100">
                  <div class="w-100">
                    <v-btn
                      @click="pushCart(item._i, 0)"
                      color="red"
                      icon="mdi-minus-circle"
                      block
                      variant="outlined"
                      rounded="lg"
                      density="comfortable"
                    ></v-btn>
                  </div>
                  <div class="w-100">
                    <v-btn
                      @click="pushCart(item._i, 1)"
                      color="primary"
                      icon="mdi-plus-circle"
                      block
                      variant="outlined"
                      rounded="lg"
                      density="comfortable"
                    ></v-btn>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex ga-2 align-center mb-3">
              <v-text-field
                label="ราคา"
                v-model="item.total"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details=""
                readonly=""
              ></v-text-field>
              <v-text-field
                label="ส่วนลด"
                v-model="item.discount"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details=""
              ></v-text-field>
            </div>
            <div class="d-flex ga-2 align-center mb-3">
              <!-- <v-text-field
                label="VAT 7%"
                v-model="item.vat"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details=""
                readonly=""
              ></v-text-field> -->
              <v-text-field
                label="ราคาสุทธิ"
                v-model="item.sum"
                density="compact"
                variant="outlined"
                rounded="lg"
                hide-details=""
                readonly=""
              ></v-text-field>
            </div>
          </div>
        </v-list-item>
      </v-list-item>
    </v-list>
    <template v-slot:append>
      <div class="pa-2">
        <v-btn color="success" prepend-icon="mdi-cash" block>ชำระเงิน</v-btn>
      </div>
    </template>
  </v-navigation-drawer>
</template>
<script setup>
import { defineProps, onMounted, ref, watch, inject } from "vue";
import { useLocalStorage } from "@/composables/useLocalStorage";
import { useCookie } from "@/composables/useCookie";
import { useJWT } from "@/composables/useJWT";
import api from "/utils/axios";
const { getCookie, deleteCookie } = useCookie();
const { getItem } = useJWT();
const globalitem = ref(getItem(getCookie("jwt")));
const { deleteItem, isItem } = useLocalStorage();

const props = defineProps({
  carts: {
    default: false,
    type: Boolean,
  },
});

const myorders = ref([]);
const getItemCart = async () => {
  let { data } = await api.get("/api/orders/getMyCart", {
    params: {
      uid: globalitem.value?.ui,
    },
  });
  if (data.data?.length > 0) {
    myorders.value = data.data;
  }else{
    myorders.value =[]
  }
};

const checkJwt = () => {
  if (!globalitem.value?.ui) {
    deleteItem("userToken");
    deleteCookie("jwt");
    window.location.href = "/login";
  } else {
    getItemCart();
  }
};
const pushCart = async (id, increase = 1) => {
  await api
    .post("/api/orders/updateCart", {
      uid: globalitem.value.ui,
      id: id,
      increase: increase,
      csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
    })
    .then((rs) => {
      return rs.data;
    })
    .then((result) => {
      getItemCart();
    })
    .catch((e) => {
      if (e.status == 401) {
        window.location.href = "/login";
      }
    });
};
onMounted(() => {});
watch(() => {
  if (props.carts) {
    checkJwt();
  }
});
</script>
