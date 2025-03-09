<template>
  <v-navigation-drawer width="500"  v-model="props.carts" location="end" temporary class="pa-3">
    <v-list  lines="two" class="mt-5 ga-2 d-flex flex-column">
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
<script setup>
import { defineProps, onMounted, ref ,watch} from "vue";
import { useLocalStorage } from "@/composables/useLocalStorage";
import { useCookie } from "@/composables/useCookie";
const { deleteItem, isItem ,getItem} = useLocalStorage();
const { deleteCookie ,getCookie} = useCookie();
const globalitem = ref(getItem(getCookie("jwt")));
const props = defineProps({
  carts: {
    default: false,
    type: Boolean,
  },
});
const items = ref([
  { text: "Real-Time", icon: "mdi-clock" },
  { text: "Audience", icon: "mdi-account" },
  { text: "Conversions", icon: "mdi-flag" },
]);

const checkJwt = () => {
  if (!globalitem.value) {
    deleteItem("userToken");
    deleteCookie("jwt");
    window.location.href = "/login";
  }
};
onMounted(()=>{
   
})
watch(()=>{
    if(props.carts){
        checkJwt()
    }
})
</script>
