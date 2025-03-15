<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme" class="position-relative">
      <App-header @themes="getTheme"></App-header>
      <v-main style="padding-top: 100px">
        <v-container>
          <v-tabs v-model="tabs" color="primary">
            <v-tab value="info">ข้อมูลส่วนตัว</v-tab>
            <v-tab value="address">ที่อยู่จัดส่ง</v-tab>
            <v-tab value="setting">ตั้งค่าส่วนตัว</v-tab>
          </v-tabs>
          <v-card>
            <v-card-text>
              <v-window v-model="tabs">
                <v-window-item value="info">
                  <div class="d-flex gap-2 justify-between w-100 ga-3 pa-2">
                    <div class="d-flex flex-column ga-2 justify-start h-100">
                      <template v-if="picture && selectedFiles.length==0">
                        <v-img rounded="lg" :width="300" cover :src="picture"></v-img>
                      </template>
                      <v-img
                        v-else
                        rounded="lg"
                        :width="300"
                        cover
                        :src="selectedFiles[0]?.preview ?? '/src/assets/user.png'"
                      ></v-img>
                      <input
                        type="file"
                        ref="fileInput"
                        accept="image/*"
                        hidden
                        @change="onFileChange"
                      />
                      <v-btn rounded="lg" @click="openFilePicker"
                        >เปลี่ยนรูปโปรไฟล์</v-btn
                      >
                    </div>
                    <div class="d-flex flex-column w-100">
                      <span class="text-grey-darken-1">คำนำหน้า</span>
                      <v-select
                        v-model="title"
                        clearable
                        label="คำนำหน้า"
                        variant="solo"
                        :items="titles"
                      ></v-select>
                      <span class="text-grey-darken-1">เพศ</span>
                      <v-select
                        v-model="gender"
                        clearable
                        label="เพศ"
                        variant="solo"
                        :items="genderList"
                        item-title="name"
                        item-value="_i"
                      ></v-select>
                      <span class="text-grey-darken-1">ชื่อ</span>
                      <v-text-field
                        v-model="fname"
                        clearable
                        label="ชื่อ"
                        variant="solo"
                      ></v-text-field>
                      <span class="text-grey-darken-1">นามสกุล</span>
                      <v-text-field
                        v-model="lname"
                        clearable
                        label="นามสกุล"
                        variant="solo"
                      ></v-text-field>
                      <span class="text-grey-darken-1">ชื่อเล่น (ถ้ามี)</span>
                      <v-text-field
                        v-model="nickname"
                        clearable
                        label="ชื่อเล่น"
                        variant="solo"
                      ></v-text-field>
                      <span class="text-grey-darken-1">อีเมล</span>
                      <v-text-field
                        v-model="email"
                        clearable
                        label="อีเมล"
                        variant="solo"
                      ></v-text-field>
                      <span class="text-grey-darken-1">เบอร์โทรศัพท์</span>
                      <v-text-field
                        v-model="phone"
                        clearable
                        label="เบอร์โทรศัพท์"
                        maxlength="10"
                        variant="solo"
                      ></v-text-field>
                      <span class="text-grey-darken-1">เลขที่บัตรประชาชน</span>
                      <v-text-field
                        v-model="id_card"
                        clearable
                        label="เลขที่บัตรประชาชน"
                        variant="solo"
                      ></v-text-field>
                      <div class="d-flex ga-3 justify-end">
                        <v-btn
                          @click="updateInfo"
                          :loading="isLoading"
                          :disabled="isLoading"
                          color="success"
                          >บันทึก</v-btn
                        >
                      </div>
                    </div>
                  </div>
                </v-window-item>
                <v-window-item value="address">
                  <div class="d-flex gap-2 justify-between w-100 ga-3 pa-2">
                    <div class="d-flex flex-column w-100">
                      <v-textarea clearable label="ที่อยู่" variant="solo"></v-textarea>
                      <div
                        class="ga-2"
                        style="display: grid; grid-template-columns: repeat(3, 1fr)"
                      >
                        <v-text-field
                          clearable
                          label="เลขที่"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field
                          clearable
                          label="หมู่ที่"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field
                          clearable
                          label="เลขที่ห้อง"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field
                          clearable
                          label="อาคาร/ตึก"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field
                          clearable
                          label="ชั้น"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field clearable label="ถนน" variant="solo"></v-text-field>
                      </div>
                      <div class="d-flex flex-wrap ga-2">
                        <v-text-field
                          clearable
                          label="ตำบล"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field
                          clearable
                          label="อำเภอ"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field
                          clearable
                          label="จังหวัด"
                          variant="solo"
                        ></v-text-field>
                        <v-text-field
                          clearable
                          label="รหัสไปรษณีย์"
                          variant="solo"
                        ></v-text-field>
                      </div>
                      <div class="d-flex ga-3 justify-end">
                        <v-btn color="success" :loading="isLoading" :disabled="isLoading"
                          >บันทึก</v-btn
                        >
                      </div>
                    </div>
                  </div>
                </v-window-item>
                <v-window-item value="setting"></v-window-item>
              </v-window>
            </v-card-text>
          </v-card>
        </v-container>
      </v-main>
    </v-app>
  </v-responsive>
  <app-footer></app-footer>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<script setup>
import { ref, onMounted, inject } from "vue";
import api from "/utils/axios";
import { Notivue, Notification, push } from "notivue";
import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
const { getItem } = useJWT();
const { getCookie } = useCookie();
const globalitem = ref(getItem(getCookie("jwt")));

const theme = ref("light");
const tabs = ref("info");
const getTheme = (e) => {
  theme.value = e;
};
const isLoading = ref(false);
const gender = ref(null);
const pd_id = ref(null);
const title = ref(null);
const fname = ref(null);
const lname = ref(null);
const id_card = ref(null);
const nickname = ref(null);
const phone = ref(null);
const email = ref(null);
const picture = ref(null);

const genderList = ref([]);
const titles = ref([
  { title: "นาย", value: 1 },
  { title: "นาง", value: 2 },
  { title: "นางสาว", value: 3 },
  { title: "อื่นๆ ไม่ระบุ", value: 4 },
]);

const getGender = async () => {
  await api
    .get("/api/Personal/getGender")
    .then((result) => {
      return result.data;
    })
    .then((result) => {
      if (result?.data) {
        genderList.value = result.data;
      }
    })
    .catch((e) => {
      push.error("เกิดข้อผิดพลาดจากระบบไม่สามารถเชื่อมต่อได้ในขณะนี้");
      console.error(e);
    });
};

const selectedFiles = ref([]);
const fileInput = ref(null);

const updateInfo = async () => {
  isLoading.value = true;
  if (!selectedFiles.value) return;
  const files = selectedFiles.value.map((e) => e.file);
  const formData = new FormData();
  formData.append("pd_id", pd_id.value);
  formData.append("gender", gender.value);
  formData.append("title", title.value);
  formData.append("first_name", fname.value);
  formData.append("last_name", lname.value);
  formData.append("id_card", id_card.value);
  formData.append("nickname", nickname.value);
  formData.append("phone", phone.value);
  formData.append("email", email.value);
  formData.append(`images[]`, files[0]);
  formData.append("csrf_token_ci_gen", getCookie("csrf_cookie_ci_gen"));
  await api
    .post("/api/Personal/updateInfo", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    .then((e) => {
      return e.data;
    })
    .then((result) => {
      if (result.success) {
        push.success("แก้ไขข้อมูลสำเร็จ");
        getInfo();
      }
    })
    .catch((e) => {
      push.error("เกิดข้อผิดพลาดจากระบบไม่สามารถเชื่อมต่อได้ในขณะนี้");
      console.error(e);
    });

  setTimeout(() => {
    isLoading.value = false;
  }, 400);
};
const getInfo = async () => {
  let { data } = await api({
    method: "get",
    url: "/api/Personal/getInfo",
    params: {
      uid: globalitem.value.ui,
    },
  });
  if (data?.data) {
    pd_id.value = data?.data?._i;
    gender.value = data?.data?.gender;
    title.value = data?.data?.title;
    fname.value = data?.data?.name?.first_name;
    lname.value = data?.data?.name?.last_name;
    id_card.value = data?.data?.info?.id_card;
    nickname.value = data?.data?.name?.nickname;
    phone.value = data?.data?.info?.phone;
    email.value = data?.data?.info?.email;
    picture.value = data?.data?.picture;
  }
};

// 📌 ฟังก์ชันเมื่อผู้ใช้เลือกไฟล์
const onFileChange = (event) => {
  selectedFiles.value = [];
  const files = Array.from(event.target.files);
  files.forEach((file) => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        selectedFiles.value.push({ file, preview: e.target.result });
      };
      reader.readAsDataURL(file);
    }
  });
};

// 📌 ฟังก์ชันลบไฟล์ที่เลือก
const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

// 📌 อ้างอิง input file แบบซ่อน
const openFilePicker = () => {
  fileInput.value.click();
};

onMounted(() => {
  getInfo();
  getGender();
});
</script>
