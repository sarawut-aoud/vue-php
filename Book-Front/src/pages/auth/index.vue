<template>
  <div id="bg-image">
    <v-card
      class="mx-auto pa-12 pb-8"
      width="800"
      elevation="8"
      max-width="448"
      rounded="lg"
      variant="elevated"
      color="white"
    >
      <div class="text-subtitle-1 text-medium-emphasis">อีเมล *</div>
      <v-text-field
        v-model="email"
        @keypress="keyEnter"
        density="compact"
        placeholder="Email"
        prepend-inner-icon="mdi-email-outline"
        variant="outlined"
      ></v-text-field>

      <div
        class="text-subtitle-1 text-medium-emphasis d-flex align-center justify-space-between"
      >
        Password
      </div>
      <v-text-field
        v-model="password"
        @keypress="keyEnter"
        :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
        :type="visible ? 'text' : 'password'"
        density="compact"
        placeholder="Enter your password"
        prepend-inner-icon="mdi-lock-outline"
        variant="outlined"
        @click:append-inner="visible = !visible"
      ></v-text-field>
      <v-btn
        @click="handleSubmit"
        :loading="isLoading"
        :disabled="isLoading"
        class="mb-4"
        color="blue"
        variant="tonal"
        block
        rounded="lg"
      >
        เข้าสู่ระบบ
      </v-btn>
      <v-checkbox v-model="remember" label="remember me"></v-checkbox>
      <v-card-text class="d-flex justify-between align-center w-100">
        <div class="w-100">
          <v-btn href="/" variant="text" class="text-blue"
            ><v-icon icon="mdi-home"></v-icon> หน้าแรก</v-btn
          >
        </div>
        <div class="w-100 text-end">
          <v-btn href="register" variant="text" class="text-blue">
            สมัครสมาชิก <v-icon icon="mdi-chevron-right"></v-icon
          ></v-btn>
        </div>
      </v-card-text>
    </v-card>
  </div>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<script setup>
import { ref } from "vue";
import api from "/utils/axios";
import { useCookie } from "@/composables/useCookie";
import { useLocalStorage } from "@/composables/useLocalStorage";
import { Notivue, Notification, push } from "notivue";
import { useJWT } from "@/composables/useJWT";
const { getCookie, setCookie, deleteCookie } = useCookie();
const { setItem, deleteItem } = useLocalStorage();
const password = ref("");
const email = ref("");
const visible = ref(false);
const isLoading = ref(false);
const remember = ref(false);
const { getItem } = useJWT();
let keyEnter = (e) => {
  if (e.keyCode === 13) handleSubmit();
};
let handleSubmit = async () => {
  isLoading.value = true;
  await api
    .post("/api/auth/authen/login", {
      email: email.value,
      password: password.value,
      csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
    })
    .then((result) => {
      return result.data;
    })
    .then((data) => {
      deleteItem("userToken");
      deleteCookie("jwt");
      if (data?.success) {
        setItem("userToken", data?.data?.token_key);
        setCookie("jwt", data?.data?.jwt, { days: remember.value });
        push.success({
          title: "Successfuly !!",
          message: "ล็อกอินเข้าสู่ระบบสำเร็จ",
        });
        setTimeout(() => {
          const url = "admin";
          const role = getItem(data?.data?.jwt);
          window.location.href = role.n == "emp" ? "/" : url;
        }, 3000);
      } else {
        push.error({
          title: "ERROR",
          message: data?.msg,
        });
        isLoading.value = false;
      }
    })
    .catch((e) => {
      push.error({
        title: "ERROR",
        message: e?.response?.data?.msg[0]
          ? e?.response?.data?.msg[0]
          : e?.response?.data?.msg,
      });
    });

  setTimeout(() => {
    isLoading.value = false;
  }, 400);
};
</script>
<style>
#bg-image {
  background-image: url(/src/assets/login/bg-01.jpg);
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
  width: 100%;
  min-height: 100vh;
  display: -webkit-box;
  display: -webkit-flex;
  display: -moz-box;
  display: -ms-flexbox;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  padding: 15px;
}
</style>
