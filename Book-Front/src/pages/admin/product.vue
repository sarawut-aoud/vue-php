<template>
  <div class="d-flex flex-column ga-2 w-100 align-center">
    <div class="w-100">
      <v-card title="หนังสือ" solo border rounded="lg">
        <v-card-text>
          <template v-slot:text>
            <v-text-field
              v-model="search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              hide-details
              single-line
            ></v-text-field>
          </template>
          <div class="d-flex flex-column ga-2">
            <div class="d-flex w-100 align-center justify-space-between">
              <div class="w-50">
                <v-text-field
                  hide-details=""
                  v-model="search"
                  placeholder="ค้นหา..."
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-magnify"
                ></v-text-field>
              </div>
              <div class="d-flex align-end">
                <v-btn
                  @click="
                    dialog = true;
                    resetData(true);
                  "
                  color="primary"
                  >เพิ่มหนังสือ</v-btn
                >
              </div>
            </div>
            <v-data-table :headers="headers" :items="productsList" :search="search">
              <template v-slot:item.picture="{ item }">
                <div class="d-flex ga-1 align-center">
                  <template v-for="(i, index) in item.picture">
                    <template v-if="index <= 2">
                      <v-avatar :image="i.path" :data-image="i.path" size="50"></v-avatar>
                    </template>
                  </template>
                </div>
              </template>
              <template v-slot:item.manage="{ item }">
                <div class="d-flex align-center ga-2">
                  <v-dialog rounded="lg" width="800">
                    <template v-slot:activator="{ props: activatorProps }">
                      <v-btn
                        v-bind="activatorProps"
                        icon="mdi-image-plus"
                        size="small"
                        rounded="lg"
                        variant="flat"
                        color="info"
                      ></v-btn>
                    </template>
                    <template v-slot:default="{ isActive }">
                      <v-card>
                        <div class="d-flex align-center">
                          <v-btn
                            class="ms-auto"
                            @click="isActive.value = false"
                            rounded="lg"
                            icon="mdi-close-circle"
                            size="small"
                            flat
                          ></v-btn>
                        </div>
                        <v-card-text>
                          <UploadImage ref="uploadRef" :existingImages="item.picture" />
                        </v-card-text>
                        <v-card-actions>
                          <div class="d-flex justify-center w-100">
                            <v-btn
                              @click="uploadImage(item._i)"
                              text="อัพโหลด"
                              color="success"
                              variant="flat"
                              prepend-icon="mdi-upload-circle"
                            ></v-btn>
                          </div>
                        </v-card-actions>
                      </v-card>
                    </template>
                  </v-dialog>

                  <v-btn
                    icon="mdi-pencil"
                    size="small"
                    rounded="lg"
                    variant="flat"
                    color="warning"
                    @click="getProductByid(item._i)"
                  ></v-btn>
                  <v-btn
                    @click="remove(item._i)"
                    icon="mdi-eraser"
                    size="small"
                    rounded="lg"
                    variant="flat"
                    color="red"
                  ></v-btn>
                  <v-divider inset vertical></v-divider>
                  <div class="d-flex flex-column ga-1 align-center">
                    <v-btn
                      @click="updateSoldOut(item._i)"
                      rounded="lg"
                      variant="outlined"
                      color="red"
                      >SOLD OUT
                    </v-btn>
                    <small v-if="item.sold_out"> <v-chip color="brown-darken-1" size="small">กำลังติดสถานะ SOLD OUT</v-chip></small>
                  </div>
                </div>
              </template>
            </v-data-table>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>

  <v-dialog v-model="dialog" width="800">
    <v-card>
      <v-card-title>
        <div class="d-flex align-center">
          <div>จัดการสินค้า</div>
          <v-btn
            class="ms-auto"
            @click="dialog = false"
            rounded="lg"
            icon="mdi-close-circle"
            size="small"
            flat
          ></v-btn>
        </div>
      </v-card-title>
      <v-card-text class="d-flex flex-column ga-3">
        <v-autocomplete
          v-model="cate_id"
          clearable
          label="เลือกหมวดหมู่"
          chips
          closable-chips
          multiple
          :items="catelists"
          variant="solo"
          item-value="_i"
          item-title="name"
          hide-details=""
        ></v-autocomplete>
        <v-text-field
          v-model="product_no"
          clearable
          label="รหัสหนังสือ"
          prepend-icon="mdi-numeric"
          hide-details=""
          variant="solo"
        ></v-text-field>
        <v-text-field
          v-model="product_name"
          clearable
          label="ชื่อรายการหนังสือ"
          prepend-icon="mdi-rename"
          hide-details=""
          variant="solo"
        ></v-text-field>
        <v-textarea
          v-model="product_detail"
          clearable
          label="รายละเอียดเพิ่มเติม (ถ้ามี)"
          prepend-icon="mdi-rename"
          hide-details=""
          variant="solo"
        ></v-textarea>
        <div>
          <v-text-field
            v-model="numberValue"
            clearable
            type="number"
            class="text-end"
            label="ราคาหนังสือ ต่อ 1 เล่ม"
            prepend-icon="mdi-cash"
            :rules="numberRules"
            variant="solo"
          ></v-text-field>
          <UploadImage ref="uploadRef" :existingImages="temp" />
        </div>
      </v-card-text>
      <template v-slot:actions>
        <v-btn variant="flat" class="ms-auto" text="ปิด" @click="dialog = false"></v-btn>
        <v-btn
          v-if="!is_update"
          variant="flat"
          color="success"
          text="บันทึกรายการ"
          @click="create"
        ></v-btn>
        <v-btn
          v-if="is_update"
          variant="flat"
          color="success"
          text="บันทึกแก้ไขรายการ"
          @click="update"
        ></v-btn>
      </template>
    </v-card>
  </v-dialog>
</template>
<script setup>
import { ref, onMounted, computed } from "vue";
import { Notivue, Notification, push } from "notivue";
import api from "/utils/axios";
import UploadImage from "@/components/UploadImage.vue"; // นำเข้า Component
import { useCookie } from "@/composables/useCookie";
const catelists = ref([]);
const search = ref("");
const { getCookie } = useCookie();
const dialog = ref(false);
const headers = ref([
  {
    align: "start",
    key: "picture",
    title: "รูปภาพ",
  },
  {
    align: "start",
    key: "name",
    title: "หมวดหมู่",
  },
  {
    align: "start",
    key: "no",
    title: "รหัสหนังสือ",
  },
  {
    align: "start",
    key: "name",
    title: "ชื่อรายการหนังสือ",
  },
  {
    align: "start",
    key: "detail",
    title: "รายละเอียด",
  },
  {
    align: "end",
    key: "price",
    title: "ราคา",
  },
  { key: "manage", title: "#" },
]);
const getList = async () => {
  let { data } = await api.get("/api/category/getList");
  if (data?.data.length > 0) {
    catelists.value = data.data;
  }
};

const numberRules = [
  (v) => !!v || "กรุณากรอกตัวเลข", // ตรวจสอบว่ามีค่าหรือไม่
  (v) => !isNaN(parseFloat(v)) || "ต้องเป็นตัวเลข", // ตรวจสอบว่าเป็นตัวเลข
  (v) => parseFloat(v) > 0 || "ต้องมากกว่า 0", // ตรวจสอบว่ามากกว่า 0
];
const numberValue = ref("");
const uploadRef = ref(null);
const product_detail = ref(null);
const product_no = ref(null);
const product_name = ref(null);
const cate_id = ref(null);
const is_update = ref(false);

const create = async () => {
  if (!uploadRef.value) return;
  const files = uploadRef.value.getFiles(); // เรียก getFiles() จาก UploadImage.vue

  if (!product_no.value) {
    push.error("กรุณากรอกรหัสหนังสือ");
    return;
  }
  if (!product_name.value) {
    push.error("กรุณากรอกชื่อรายหนังสือ");
    return;
  }
  if (!numberValue.value) {
    push.error("กรุณากรอกราคาหนังสือ");
    return;
  }

  const formData = new FormData();

  formData.append("no", product_no.value);
  formData.append("name", product_name.value);
  formData.append("detail", product_detail.value);
  formData.append("price", numberValue.value);
  formData.append("cate_id", cate_id.value);

  files.forEach((file, index) => {
    formData.append(`images[]`, file);
  });
  formData.append("csrf_token_ci_gen", getCookie("csrf_cookie_ci_gen"));
  try {
    const response = await api.post("/api/products/create", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    console.log("Upload success:", response.data);
    push.success("บันทึกข้อมูลสำเร็จ!");
    resetData();
    loadData();
  } catch (error) {
    console.error("Upload failed:", error);
    push.error("เกิดข้อผิดพลาดในการอัปโหลด");
  }
};
const uploadImage = async (id) => {
  if (!uploadRef.value) return;
  const files = uploadRef.value.getFiles(); // เรียก getFiles() จาก UploadImage.vue
  const formData = new FormData();
  files.forEach((file, index) => {
    formData.append(`images[]`, file);
  });
  formData.append("id", id);
  formData.append("csrf_token_ci_gen", getCookie("csrf_cookie_ci_gen"));
  try {
    const response = await api.post("/api/products/uploadImage", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    console.log("Upload success:", response.data);
    push.success("บันทึกข้อมูลสำเร็จ!");
    getListProduct();
  } catch (error) {
    console.error("Upload failed:", error);
    push.error("เกิดข้อผิดพลาดในการอัปโหลด");
  }
};
const temp = ref([]);
const p_id = ref(null);
const getProductByid = async (id) => {
  dialog.value = true;
  is_update.value = true;
  p_id.value = id;
  let { data } = await api.get("/api/products/getList/" + id);
  if (data?.data) {
    let rs = data.data;
    product_no.value = rs.no;
    product_name.value = rs.name;
    product_detail.value = rs.detail;
    numberValue.value = rs.price;
    cate_id.value = rs.cate_id.map((e) => e._i);
    temp.value = rs.picture;
  }
};

const update = async () => {
  Swal.fire({
    title: "ยืนยันการแก้ไขข้อมูล ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "ตกลง",
    cancelButtonText: "ยกเลิก",
  }).then(async (result) => {
    if (result.isConfirmed) {
      if (!uploadRef.value) return;
      const files = uploadRef.value.getFiles(); // เรียก getFiles() จาก UploadImage.vue

      if (!product_no.value) {
        push.error("กรุณากรอกรหัสหนังสือ");
        return;
      }
      if (!product_name.value) {
        push.error("กรุณากรอกชื่อรายหนังสือ");
        return;
      }
      if (!numberValue.value) {
        push.error("กรุณากรอกราคาหนังสือ");
        return;
      }

      const formData = new FormData();

      formData.append("p_id", p_id.value);
      formData.append("no", product_no.value);
      formData.append("name", product_name.value);
      formData.append("detail", product_detail.value);
      formData.append("price", numberValue.value);
      formData.append("cate_id", cate_id.value);

      files.forEach((file, index) => {
        formData.append(`images[]`, file);
      });
      formData.append("csrf_token_ci_gen", getCookie("csrf_cookie_ci_gen"));
      try {
        const response = await api.post("/api/products/update", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        console.log("Upload success:", response.data);
        push.success("บันทึกข้อมูลสำเร็จ!");
        resetData();
        loadData();
      } catch (error) {
        console.error("Upload failed:", error);
        push.error("เกิดข้อผิดพลาดในการอัปโหลด");
      }
    }
  });
};
const remove = async (id) => {
  Swal.fire({
    title: "ต้องการลบข้อมูลสินค้า ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      await api
        .post("/api/products/remove/" + id, {
          csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
        })
        .then((result) => {
          return result.data;
        })
        .then((data) => {
          if (data.status) {
            push.success("ลบข้อมูลสำเร็จ");
            getListProduct();
          }
        });
    }
  });
};
const resetData = (value) => {
  dialog.value = value;
  is_update.value = !value;
  product_no.value = null;
  product_name.value = null;
  product_detail.value = null;
  numberValue.value = null;
  cate_id.value = [];
  temp.value = [];
  p_id.value = null;
};
const productsList = ref([]);
const getListProduct = async () => {
  productsList.value = [];
  let { data } = await api.get("/api/products/getList");
  if (data?.data.length > 0) {
    productsList.value = data.data;
  }
};

const updateSoldOut = async (id) => {
  Swal.fire({
    title: "ต้องการเปลี่ยนสถานะสินค้า ?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "ตกลง",
    cancelButtonText: "ยกเลิก",
  }).then(async (result) => {
    if (result.isConfirmed) {
      let { data } = await api
        .post("/api/products/updateSoldOut/" + id, {
          csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
        })
        .then((result) => {
          return result.data;
        })
        .then((data) => {
          if (data.status) {
            push.success("ปรับสถานะสำเร็จ");
            getListProduct();
          }
        });
    }
  });
};

const loadData = () => {
  getList();
  getListProduct();
};
onMounted(() => {
  loadData();
});
</script>
