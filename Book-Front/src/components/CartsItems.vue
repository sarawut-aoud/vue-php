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
                <template v-slot:prev="{ props }">
                  <v-btn
                    class="mt-auto mb-2"
                    icon="mdi-chevron-left"
                    size="small"
                    variant="outlined"
                    color="white"
                    @click="props.onClick"
                  >
                  </v-btn>
                </template>
                <template v-slot:next="{ props }">
                  <v-btn
                    class="mt-auto mb-2"
                    icon="mdi-chevron-right"
                    size="small"
                    variant="outlined"
                    color="white"
                    @click="props.onClick"
                  >
                  </v-btn>
                </template>
                <v-window-item v-for="n in item?.product?.picture" :key="`card-${n}`">
                  <v-img :src="n.path" width="200" height="100" cover=""></v-img>
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
                  readonly=""
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
                readonly=""
                hide-details=""
              ></v-text-field>
            </div>
            <div class="d-flex ga-2 align-center mb-3">
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
      <div class="d-flex flex-column ga-3 pa-2">
        <div class="d-flex justify-space-between">
          <div class="w-100">ราคา</div>
          <div class="w-100 text-end">{{ sum_price }} บาท</div>
        </div>
        <div class="d-flex justify-space-between">
          <div class="w-100">ส่วนลด</div>
          <div class="w-100 text-end">{{ sum_discount }} บาท</div>
        </div>
        <div class="d-flex justify-space-between">
          <div class="w-100">ราคาสุทธิ</div>
          <div class="w-100 text-end">{{ sum_total }} บาท</div>
        </div>
        <v-divider></v-divider>
        <div class="mt-auto">
          <v-btn @click="payments" color="success" prepend-icon="mdi-cash" block
            >ชำระเงิน</v-btn
          >
        </div>
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
  } else {
    myorders.value = [];
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
const sum_price = ref(0);
const sum_discount = ref(0);
const sum_total = ref(0);
const order_id_group = ref([]);
watch(() => {
  if (myorders.value.length > 0) {
    myorders.value.forEach((e, index) => {
      sum_price.value += e.sum;
      sum_total.value += e.total;
      sum_discount.value += e.discount;
    });
    order_id_group.value = myorders.value.map((e) => e._i);
  }
});

const payments = async () => {
  await api
    .post("/api/orders/paymentOrder", {
      csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
      uid: globalitem.value?.ui,
      price: sum_price.value,
      total: sum_total.value,
      order_id: order_id_group.value,
      discount: sum_discount.value,
    })
    .then((rs) => {
      return rs.data;
    })
    .then((result) => {
      if (result.status) {
        window.location.href = "/payments";
      }
    })
    .catch((e) => {
      console.error(e);
    });
};
</script>
