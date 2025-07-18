<template>

    <Head title="POS" />
    <Banner />
    <div class="flex flex-col items-center justify-start min-h-screen py-8 space-y-4 bg-gray-100 md:px-36 px-16">
        <!-- Include the Header -->
        <Header />

        <div class="w-full md:w-5/6  py-12 space-y-16">
            <div class="flex items-center justify-between space-x-4">
                <div class="flex w-full space-x-4">
                    <Link href="/">
                    <img src="/images/back-arrow.png" class="w-14 h-14" />
                    </Link>
                    <p class="pt-3 text-4xl font-bold tracking-wide text-black uppercase">
                        PoS
                    </p>
                </div>

                <div class="flex items-center justify-between w-full space-x-4">
                    <p class="text-3xl font-bold tracking-wide text-black">
                        Order ID : #{{ orderid }}
                    </p>

                    <p class="text-3xl text-black cursor-pointer">
                        <i @click="refreshData" class="ri-restart-line"></i>
                    </p>
                </div>

            </div>
            <div class="flex md:flex-row flex-col w-full gap-4">
                <div class="flex w-full p-8 border-4 border-black rounded-3xl">
                    <div class="flex flex-col items-start justify-center w-full md:px-12 px-4">
                        <div v-if="isReturnMode" class="mb-3 flex items-center">
                            <input type="checkbox" id="returnBill" v-model="isReturnBill"
                                :checked="selectedOrder?.is_return_bill == 1"
                                :disabled="selectedOrder?.is_return_bill == 1"
                                class="h-8 w-8 text-black-600 border-black rounded focus:ring focus:ring-black-500">


                            <label for="returnBill" class="ml-2 text-2xl font-bold tracking-wide text-black">Return
                                Bill</label>
                        </div>








                        <div
                            class="flex flex-col md:flex-row md:items-center justify-between w-full mb-6 gap-4 md:gap-0">

                            <!-- Title -->
                            <h2 class="text-3xl md:text-5xl font-extrabold text-gray-900">
                                Billing Details
                            </h2>

                            <span class="inline-flex items-center px-8 py-2 rounded-full text-2xl  font-semibold"
                                :class="isWholesale ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'">
                                {{ isWholesale ? 'Wholesale' : 'Retail' }}
                            </span>

                            <!-- Action Area: Toggle + Buttons -->
                            <div class="flex flex-col md:flex-row items-center gap-4">



                                <div class="flex flex-col md:flex-row items-center gap-4">
                                    <!-- Wholesale / Retail Toggle -->
                                    <div class="flex items-center bg-gray-100 rounded-full px-3 py-1">
                                        <span class="text-xl font-semibold cursor-pointer"
                                            :class="!isWholesale ? 'text-blue-600' : 'text-gray-500'"
                                            @click="setMode(false)">
                                            Retail
                                        </span>

                                        <div class="mx-2 w-10 h-6 bg-blue-300 rounded-full relative cursor-pointer"
                                            @click="toggleMode">
                                            <div class="w-5 h-6 bg-white rounded-full shadow transition-transform duration-300"
                                                :class="isWholesale ? 'translate-x-5' : 'translate-x-0'"></div>
                                        </div>

                                        <span class="text-xl font-semibold cursor-pointer"
                                            :class="isWholesale ? 'text-blue-600' : 'text-gray-500'"
                                            @click="setMode(true)">
                                            Wholesale
                                        </span>
                                    </div>
                                </div>








                                <!-- User Manual Button -->
                                <button @click="isSelectModalOpen = true"
                                    class="flex items-center px-4 py-2 rounded-lg bg-blue-100 hover:bg-blue-200 transition">
                                    <img src="/images/selectpsoduct.svg" alt="Manual Icon" class="w-5 h-5 mr-2" />
                                    <span class="text-blue-700 font-medium text-base">User Manual</span>
                                </button>

                                <!-- Orders & Customer Details Button -->
                                <button @click="isModalOpen = true"
                                    class="flex items-center px-4 py-2 rounded-lg bg-blue-100 hover:bg-blue-200 transition">
                                    <img src="/images/selectpsoduct.svg" alt="Order Icon" class="w-5 h-5 mr-2" />
                                    <span class="text-blue-700 font-medium text-base">Orders & Customer Details</span>
                                </button>

                            </div>
                        </div>





                        <div
                            class="flex flex-col w-full my-5 space-y-4 md:flex-row md:items-end md:space-y-0 md:justify-between border-2 border-black rounded-2xl px-6 py-4">

                            <!-- Barcode Input Section -->
                            <div class="flex w-full md:w-3/4 items-center space-x-4">
                                <input v-model="form.barcode" id="search" type="text" placeholder="Enter BarCode Here!"
                                    class="w-full h-16 px-4 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />

                                <button @click="submitBarcode"
                                    class="px-8 py-4 text-xl font-bold text-white uppercase bg-blue-600 hover:bg-blue-700 rounded-xl transition">
                                    Enter
                                </button>
                            </div>

                            <!-- Credit Bill Toggle Section -->
                            <div class="flex items-center justify-end w-full md:w-1/4 mt-4 md:mt-0">
                                <label for="credit_bill" class="flex items-center space-x-3 text-lg text-black">
                                    <input id="credit_bill" type="checkbox" v-model="credit_bill"
                                        class="w-6 h-6 text-blue-600 border-2 border-black rounded focus:ring-2 focus:ring-blue-500" />
                                    <span class="font-semibold">Credit Bill</span>
                                </label>
                            </div>
                        </div>










                        <div class="w-full text-center pb-4">
                            <p v-if="products.length === 0" class="text-2xl text-red-500">
                                No Products to show
                            </p>
                        </div>

                        <div class="flex items-center w-full py-4 border-b border-black" v-for="item in products"
                            :key="item.id">
                            <div class="flex w-1/6">
                                <img :src="item.image ? `/${item.image}` : '/images/placeholder.jpg'
                                    " alt="Supplier Image" class="object-cover w-16 h-16 border border-gray-500" />
                            </div>

                            <div class="flex flex-col justify-between w-5/6">
                                <p class="text-xl text-black">
                                    {{ item.name }}
                                </p>
                                <div class="flex items-center justify-between w-full">
                                    <div class="flex space-x-4">
                                        <p @click="incrementQuantity(item.id)"
                                            class="flex items-center justify-center w-10 h-8 text-white bg-black rounded cursor-pointer">
                                            <i class="ri-add-line"></i>
                                        </p>

                                        <input type="number" v-model="item.quantity" min="0"
                                            class="bg-[#D9D9D9] border-2 border-black h-10 w-24 text-black flex justify-center items-center rounded text-center" />
                                        <p @click="decrementQuantity(item.id)"
                                            class="flex items-center justify-center w-10 h-8 text-white bg-black rounded cursor-pointer">
                                            <i class="ri-subtract-line"></i>
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-center">
                                        <div>
                                            <p @click="applyDiscount(item.id)" v-if="
                                                (isWholesale ? item.wholesale_discount : item.discount) &&
                                                (isWholesale ? item.wholesale_discount : item.discount) > 0 &&
                                                item.apply_discount === false &&
                                                !appliedCoupon
                                            " class="cursor-pointer py-1 text-center px-4 bg-green-600 rounded-xl font-bold text-white tracking-wider">
                                                Apply {{ isWholesale ? item.wholesale_discount : item.discount }}% Off
                                            </p>

                                            <!-- Return Reason Dropdown -->
                                            <div v-if="isReturnBill" class="mt-2 ml-2">
                                                <select v-model="item.returnReason"
                                                    class="w-full border border-gray-400 p-2 rounded" required>
                                                    <option value="" disabled selected>Select a reason</option>
                                                    <option v-for="reason in props.returnReasons" :key="reason.id"
                                                        :value="reason.id">
                                                        {{ reason.reason }}
                                                    </option>
                                                </select>
                                            </div>

                                            <p v-if="
                                                (isWholesale ? item.wholesale_discount : item.discount) &&
                                                (isWholesale ? item.wholesale_discount : item.discount) > 0 &&
                                                item.apply_discount === true &&
                                                !appliedCoupon
                                            " @click="removeDiscount(item.id)" class="cursor-pointer py-1 text-center px-4 bg-red-600 rounded-xl font-bold text-white tracking-wider">
                                                Remove {{ isWholesale ? item.wholesale_discount : item.discount }}% Off
                                            </p>

                                            <p class="text-2xl font-bold text-black text-right">
                                                {{ isWholesale ? item.whole_price : item.selling_price }} LKR
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end w-1/6">
                                <p @click="removeProduct(item.id)"
                                    class="text-3xl text-black border-2 border-black rounded-full cursor-pointer">
                                    <i class="ri-close-line"></i>
                                </p>
                            </div>
                        </div>


                        <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-6">

                            <!-- LEFT: Billing Summary -->
                            <div class="w-full pt-6 space-y-4 border border-black rounded-xl p-6 bg-white shadow-md">
                                <!-- Sub Total -->
                                <div class="flex items-center justify-between">
                                    <p class="text-xl font-medium">Sub Total</p>
                                    <p class="text-xl font-semibold text-gray-800">{{ subtotal }} LKR</p>
                                </div>

                                <!-- Discount -->
                                <div class="flex items-center justify-between border-b border-gray-400 pb-3">
                                    <p class="text-xl font-medium">Discount</p>
                                    <p class="text-xl font-semibold text-red-600">({{ totalDiscount }} LKR)</p>
                                </div>

                                <!-- Custom Discount -->
                                <div class="flex items-center justify-between border-b border-gray-400 pb-3">
                                    <p class="text-xl font-medium">Custom Discount</p>
                                    <div class="flex items-center space-x-2">
                                        <CurrencyInput v-model="custom_discount" @blur="validateCustomDiscount"
                                            placeholder="Enter value"
                                            class="rounded-md px-3 py-1   text-black focus:ring-2 focus:ring-blue-500" />
                                        <select v-model="custom_discount_type"
                                            class="px-8 py-1 border border-gray-300 rounded-md text-black focus:ring-2 focus:ring-blue-500">
                                            <option value="percent">%</option>
                                            <option value="fixed">Rs</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Cash -->
                                <div class="flex items-center justify-between border-b border-gray-400 pb-3">
                                    <p class="text-xl font-medium">Cash</p>
                                    <div class="flex items-center">
                                        <CurrencyInput ref="cashInputRef" v-model="cash" :options="{ currency: 'LKR' }"
                                            class="rounded-md px-3 py-1   text-black" tabindex="1" />
                                        <span class="ml-2 text-lg font-medium">LKR</span>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="flex items-center justify-between pt-3">
                                    <p class="text-3xl font-bold text-black">Total</p>
                                    <p class="text-3xl font-bold text-black">{{ total }} LKR</p>
                                </div>

                                <!-- Balance -->
                                <div class="flex items-center justify-between border-t border-gray-400 pt-3">
                                    <p class="text-xl font-medium">Balance</p>
                                    <p class="text-xl font-semibold text-green-700">{{ balance }} LKR</p>
                                </div>


                                <div>
                                    <label for="total_to_include_guide"
                                        class="mb-1 text-lg font-semibold text-gray-700">
                                        Total Not Including Guide (LKR)
                                    </label>
                                    <input id="total_to_include_guide" type="number" v-model="total_to_include_guide"
                                        placeholder="0.00" readonly
                                        class="w-full px-4 py-3 border border-gray-300 rounded-md text-lg  focus:ring-2 focus:ring-blue-500" />
                                </div>



                                <!-- Coupon Code -->
                                <div class="pt-4">
                                    <div class="relative flex items-center">
                                        <input id="coupon" v-model="couponForm.code" type="text"
                                            placeholder="Enter Coupon Code"
                                            class="w-full h-14 px-6 pr-40 text-lg text-gray-800 border-2 border-gray-300 rounded-full focus:ring-2 focus:ring-blue-500" />

                                        <template v-if="!appliedCoupon">
                                            <button @click="submitCoupon"
                                                class="absolute right-2 top-1.5 h-11 px-6 text-sm font-semibold text-white bg-blue-600 rounded-full hover:bg-blue-700">
                                                Apply Coupon
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click="removeCoupon"
                                                class="absolute right-2 top-1.5 h-11 px-6 text-sm font-semibold text-white bg-red-600 rounded-full hover:bg-red-700">
                                                Remove Coupon
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT: Guide Info + Payment Method -->
                            <div class="w-full space-y-6">

                                <!-- <div class="w-full border-2 border-black rounded-2xl p-6 bg-gray-50 shadow-md">
                                    <h3 class="text-2xl font-bold text-black mb-4">Guide Information</h3>

                                    <div class="flex items-center space-x-3 mb-4">
                                        <input id="pending_status" type="checkbox" v-model="guide_pending"
                                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                        <label for="pending_status" class="text-lg font-semibold text-gray-700">
                                            Mark as Pending
                                        </label>
                                    </div>

                                    <div class="flex flex-col mb-4">
                                        <label for="guide_name" class="mb-1 text-lg font-semibold text-gray-700">Guide
                                            Name</label>
                                        <input id="guide_name" type="text" v-model="guide_name"
                                            placeholder="Enter guide name"
                                            class="px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                                    </div>

                                    <div class="flex flex-col md:flex-row gap-6 mb-4">
                                        <div class="w-full">
                                            <label for="guide_comi" class="mb-1 text-lg font-semibold text-gray-700">
                                                Guide Commission (% )</label>
                                            <input id="guide_comi" type="number" v-model="guide_comi"
                                                placeholder="e.g., 10" min="0" max="100"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                        <div class="w-full">
                                            <label for="guide_cash"
                                                class="mb-1 text-lg font-semibold text-gray-700">Cash (LKR)</label>
                                            <input id="guide_cash" type="number" v-model="guide_cash" readonly
                                                class="w-full px-4 py-3 bg-gray-100 text-black border border-gray-300 rounded-md" />
                                        </div>
                                    </div>



                                    <div class="flex flex-col md:flex-row gap-6 mb-4">


                                        <div class="w-full">
                                            <label for="guide_cash"
                                                class="mb-1 text-lg font-semibold text-gray-700">Cash (LKR)</label>
                                            <input id="guide_cash" type="number" v-model="guide_cash" readonly
                                                class="w-full px-4 py-3 bg-gray-100 text-black border border-gray-300 rounded-md" />
                                        </div>
                                    </div>

                                </div> -->



                                <div v-if="selectedPaymentMethod === 'online'"
                                    class="w-full border-2 border-black rounded-2xl p-6 bg-gray-50 shadow-md">



                                    <!-- Cheque Form (Visible only if Cheqe selected) -->
                                    <div class="mt-6  ">
                                        <h4 class="text-xl font-semibold text-black mb-4">Cheque Information</h4>

                                        <div class="flex flex-col md:flex-row gap-6 mb-4">
                                            <div class="w-full">
                                                <label for="cheque_number"
                                                    class="mb-1 text-lg font-semibold text-gray-700">Cheque
                                                    Number</label>
                                                <input id="cheque_number" type="text" v-model="cheque_number"
                                                    placeholder="Enter cheque number"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                                            </div>

                                            <div class="w-full">
                                                <label for="bank_name"
                                                    class="mb-1 text-lg font-semibold text-gray-700">Bank Name</label>
                                                <input id="bank_name" type="text" v-model="bank_name"
                                                    placeholder="Enter bank name"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                                            </div>
                                        </div>

                                        <div class="flex flex-col md:flex-row gap-6 mb-4">
                                            <div class="w-full">
                                                <label for="cheque_date"
                                                    class="mb-1 text-lg font-semibold text-gray-700">Cheque Date</label>
                                                <input id="cheque_date" type="date" v-model="cheque_date"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                                            </div>

                                            <div class="w-full">
                                                <label for="cheque_amount"
                                                    class="mb-1 text-lg font-semibold text-gray-700">Cheque Amount
                                                    (LKR)</label>
                                                <input id="cheque_amount" type="number" v-model="cheque_amount"
                                                    placeholder="Enter amount"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="cheque_notes"
                                                class="mb-1 text-lg font-semibold text-gray-700">Notes
                                                (Optional)</label>
                                            <textarea id="cheque_notes" v-model="cheque_notes" rows="3"
                                                placeholder="Any special notes"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"></textarea>
                                        </div>
                                    </div>









                                </div>







                                <!-- Payment Method -->
                                <div class="w-full bg-white p-6 rounded-xl border border-black shadow-md">
                                    <p class="text-xl font-semibold text-center mb-4">Payment Method</p>
                                    <div class="flex justify-center space-x-8">
                                        <!-- Cash Option -->
                                        <div @click="selectedPaymentMethod = 'cash'" :class="[
                                            'cursor-pointer w-[100px] p-2 border border-black rounded-xl flex flex-col items-center',
                                            selectedPaymentMethod === 'cash' ? 'bg-yellow-400 font-bold shadow' : 'hover:bg-gray-100'
                                        ]">
                                            <img src="/images/money-stack.png" alt="Cash" class="w-16" />
                                            <span class="mt-2 text-sm">Cash</span>
                                        </div>

                                        <!-- Card Option -->
                                        <div @click="selectedPaymentMethod = 'card'" :class="[
                                            'cursor-pointer w-[100px] p-2 border border-black rounded-xl flex flex-col items-center',
                                            selectedPaymentMethod === 'card' ? 'bg-yellow-400 font-bold shadow' : 'hover:bg-gray-100'
                                        ]">
                                            <img src="/images/bank-card.png" alt="Card" class="w-16" />
                                            <span class="mt-2 text-sm">Card</span>
                                        </div>




                                        <div @click="selectedPaymentMethod = 'online'" :class="[
                                            'cursor-pointer w-[100px] p-2 border border-black rounded-xl flex flex-col items-center',
                                            selectedPaymentMethod === 'online' ? 'bg-yellow-400 font-bold shadow' : 'hover:bg-gray-100'
                                        ]">
                                            <img src="/images/bank-check.png" alt="Cheque" class="w-16" />
                                            <span class="mt-2 text-sm">Cheque </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Confirm Order Button -->
                                <div class="w-full">
                                    <button ref="confirmButtonRef"
                                        @click="selectedOrder?.order_id ? updateOrder() : submitOrder()" type="button"
                                        :disabled="products.length === 0" :class="[
                                            'w-full bg-black py-4 text-xl font-bold tracking-wide text-white uppercase rounded-xl transition',
                                            products.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-800'
                                        ]" tabindex="2">
                                        <i class="pr-3 ri-add-circle-fill"></i>
                                        {{ selectedOrder?.order_id ? 'Update Order' : 'Confirm Order' }}
                                    </button>
                                </div>
                            </div>




                        </div>








                    </div>
                </div>
            </div>
        </div>
    </div>
    <PosSuccessModel :open="isSuccessModalOpen" @update:open="handleModalOpenUpdate" :products="products.map((item, index) => ({
        ...item,
        returnReason: selectedOrder?.sale_items?.[index]?.return_reason?.reason || 'No Reason'
    }))" :employee="employee" :cashier="loggedInUser" :customer="customer" :orderid="orderid" :cash="cash"
        :isWholesale="isWholesale" :credit_bill="credit_bill" :balance="balance" :subTotal="subtotal"
        :totalDiscount="totalDiscount" :total="total" :custom_discount_type="custom_discount_type"
        :custom_discount="custom_discount" :return_date="selectedOrder?.return_date || ''"
        :is_return_bill="selectedOrder?.is_return_bill || 0" :guide_pending="guide_pending" :guide_comi="guide_comi"
        :guide_cash="guide_cash" :guide_name="guide_name" :paymentMethod="selectedPaymentMethod" />



    <AlertModel v-model:open="isAlertModalOpen" :message="message" />

    <SelectProductModel v-model:open="isSelectModalOpen" :allcategories="allcategories" :colors="colors" :sizes="sizes"
        @selected-products="handleSelectedProducts" />
    <Footer />

    <template v-if="isModalOpen">
        <div class="fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50">
            <div class="bg-white rounded-3xl p-8 w-full max-w-2xl relative shadow-xl overflow-auto max-h-[90vh]">
                <!-- Close Button -->
                <button @click="isModalOpen = false"
                    class="absolute top-3 right-3 text-black text-3xl font-bold">&times;</button>

                <div class="flex flex-col w-full">
                    <div class="flex flex-col w-full">
                        <div
                            class="flex flex-col items-center justify-center w-full space-y-8 border-4 border-black rounded-3xl p-4 mb-4">
                            <p class="text-4xl font-bold text-black">All Orders
                            </p>
                            <div class="flex items-center justify-center w-full mt-4 mb-4">
                                <input v-model="form.barcode" id="search" type="text"
                                    placeholder="Enter Bill BarCode Here!"
                                    class="w-full h-16 px-4 rounded-l-2xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    autofocus />
                                <button @click="fetchreturn"
                                    class="px-12 py-4 text-2xl font-bold tracking-wider focus:outline-none focus:ring-2 focus:ring-blue-500 text-white uppercase bg-blue-600 rounded-r-xl">
                                    Enter
                                </button>
                            </div>
                        </div>
                        <div class="p-8 space-y-8 bg-black shadow-lg rounded-3xl">
                            <p class="mb-4 text-3xl font-bold text-white">Customer Details</p>
                            <div class="mb-3">
                                <input v-model="customer.name" type="text" placeholder="Enter Customer Name"
                                    class="w-full px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div class="flex gap-2 mb-3 text-black">
                                <input v-model="customer.contactNumber" type="text"
                                    placeholder="Enter Customer Contact Number"
                                    class="flex-grow px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div class="text-black">
                                <input v-model="customer.email" type="email" placeholder="Enter Customer Email"
                                    class="w-full px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div class="text-black">
                                <select required v-model="employee_id" id="employee_id"
                                    class="w-full px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="" disabled>Select an Employee</option>
                                    <option v-for="employee in allemployee" :key="employee.id" :value="employee.id">
                                        {{ employee.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end mt-6">
                            <button @click="addCustomerDetails"
                                class="px-8 py-3 text-lg font-bold tracking-wider text-white uppercase bg-blue-600 rounded-xl hover:bg-blue-700 focus:outline-none">
                                Add
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </template>

</template>

<script setup>
import Header from "@/Components/custom/Header.vue";
import Footer from "@/Components/custom/Footer.vue";
import Banner from "@/Components/Banner.vue";
import PosSuccessModel from "@/Components/custom/PosSuccessModel.vue";
import AlertModel from "@/Components/custom/AlertModel.vue";
import { useForm, router } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, onUnmounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import CurrencyInput from "@/Components/custom/CurrencyInput.vue";
import SelectProductModel from "@/Components/custom/SelectProductModel.vue";
import ProductAutoComplete from "@/Components/custom/ProductAutoComplete.vue";
import { generateOrderId } from "@/Utils/Other.js";

const product = ref(null);
const returnReasons = ref(null);
const error = ref(null);
const products = ref([]);
const isSuccessModalOpen = ref(false);
const isAlertModalOpen = ref(false);
const message = ref("");
const appliedCoupon = ref(null);
const cash = ref(0);
const custom_discount = ref(0);
const isSelectModalOpen = ref(false);
const isModalOpen = ref(false);
const isWholesale = ref(false)
const custom_discount_type = ref('percent');
const orderid = computed(() => generateOrderId());
const isReturnMode = ref(false);
const isReturnBill = ref(false);
const selectedOrder = ref(null);
const isSubmitting = ref(false);
const guide_name = ref("");
const guide_comi = ref(0);
const guide_cash = ref(0);
const guide_pending = ref(false);
const credit_bill = ref(false);
const cheque_number = ref('');
const bank_name = ref('');
const cheque_date = ref('');
const cheque_amount = ref('');
const cheque_notes = ref('');
const cashInputRef = ref(null);
const confirmButtonRef = ref(null);
const shouldFocusCashNext = ref(false);

const focusOnCashField = () => {
    nextTick(() => {
        if (cashInputRef.value) {
            cashInputRef.value.focus();
        }
    });
};


const handleModalOpenUpdate = (newValue) => {
    isSuccessModalOpen.value = newValue;
    if (!newValue) {
        refreshData();
    }
};

const addCustomerDetails = () => {
    isModalOpen.value = false;
};

const props = defineProps({
    loggedInUser: Object,
    allcategories: Array,
    allemployee: Array,
    colors: Array,
    sizes: Array,
    returnReasons: Array,
});

const discount = ref(0);
const customer = ref({
    name: "",
    countryCode: "",
    contactNumber: "",
    email: "",
});

const toggleMode = () => {
    isWholesale.value = !isWholesale.value
}

const setMode = (value) => {
    isWholesale.value = value
}

const employee_id = ref("");

const selectedPaymentMethod = ref("cash");

const refreshData = () => {
    router.visit(route("pos.index"), {
        preserveScroll: false,
        preserveState: false,
    });
};

const removeProduct = (id) => {
    products.value = products.value.filter((item) => item.id !== id);
};

const removeCoupon = () => {
    appliedCoupon.value = null;
    couponForm.code = "";
};

const incrementQuantity = (id) => {
    const product = products.value.find((item) => item.id === id);
    if (product) {
        product.quantity += 1;
        shouldFocusCashNext.value = true;
    }
};

const decrementQuantity = (id) => {
    const product = products.value.find((item) => item.id === id);
    if (product && product.quantity > 1) {
        product.quantity -= 1;
        shouldFocusCashNext.value = true;
    }
};

const orderId = computed(() => {
    const characters =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    return Array.from({ length: 6 }, () =>
        characters.charAt(Math.floor(Math.random() * characters.length))
    ).join("");
});

const submitOrder = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    if (balance.value < 0) {
        isAlertModalOpen.value = true;
        message.value = "Cash is not enough";
        isSubmitting.value = false;
        return;
    }

    if (selectedPaymentMethod.value === 'online') {
        if (
            !cheque_number.value ||
            !bank_name.value ||
            !cheque_date.value ||
            !cheque_amount.value
        ) {
            isAlertModalOpen.value = true;
            message.value = "Please fill in all cheque fields (number, bank, date, amount)";
            isSubmitting.value = false;
            return;
        }
    }
    try {
        const payload = {
            customer: customer.value,
            products: products.value,
            employee_id: employee_id.value,
            paymentMethod: selectedPaymentMethod.value,
            userId: props.loggedInUser.id,
            orderid: orderid.value,
            cash: cash.value,
            custom_discount: custom_discount.value,
            isWholesale: isWholesale.value ? 1 : 0,
            guide_name: guide_name.value,
            guide_comi: guide_comi.value,
            guide_cash: guide_cash.value,
            total_to_include_guide: total_to_include_guide.value,
            credit_bill: credit_bill.value,
            guide_pending: guide_pending.value ? 1 : 0,
        };

        if (selectedPaymentMethod.value === 'online') {
            payload.online = {
                cheque_number: cheque_number.value,
                bank_name: bank_name.value,
                cheque_date: cheque_date.value,
                amount: cheque_amount.value,
                notes: cheque_notes.value || '',
            };
        }
        const response = await axios.post("/pos/submit", payload);

        isSuccessModalOpen.value = true;
        console.log("Order submitted successfully:", response.data);
    } catch (error) {
        if (error.response?.status === 423) {
            isAlertModalOpen.value = true;
            message.value = error.response.data.message;
        } else {
            isAlertModalOpen.value = true;
            message.value = "An error occurred while submitting the order.";
        }
        console.error("Order submission error:", error);
    } finally {
        isSubmitting.value = false;
    }
};

const updateOrder = async () => {
    console.log("Selected Order:", selectedOrder.value);

    if (!selectedOrder.value || !selectedOrder.value.order_id) {
        console.error("No order selected to update");
        isAlertModalOpen.value = true;
        message.value = "No order selected. Please select an order first.";
        return;
    }

    // Validate return reasons if this is a return bill
    if (isReturnBill.value) {
        // Check if any product quantity changed to 0 or has been reduced
        const returningProducts = products.value.filter(
            product => product.quantity < product.pivot?.quantity
        );

        // Make sure each returning product has a reason selected
        const missingReasons = returningProducts.filter(
            product => !product.returnReason && product.quantity < product.pivot?.quantity
        );

        if (missingReasons.length > 0) {
            isAlertModalOpen.value = true;
            message.value = "Please select a return reason for all returned products.";
            return;
        }
    }

    try {
        const response = await axios.put(`/pos/update/${selectedOrder.value.order_id}`, {
            customer: customer.value,
            products: products.value,
            employee_id: employee_id.value,
            paymentMethod: selectedPaymentMethod.value,
            cash: cash.value,
            custom_discount: custom_discount.value,
            custom_discount_type: custom_discount_type.value,
            appliedCoupon: appliedCoupon.value,
            returnBill: isReturnBill.value,
            isWholesale: isWholesale.value,
        });

        isSuccessModalOpen.value = true;
        console.log("Order updated successfully:", response.data);
    } catch (error) {
        console.error("Error updating order:", error);
        isAlertModalOpen.value = true;
        message.value = error.response?.data?.message || "Failed to update order. Please try again.";
    }
};


const subtotal = computed(() => {
    return products.value.reduce((total, item) => {
        const price = isWholesale.value ? parseFloat(item.whole_price) : parseFloat(item.selling_price);
        return total + price * item.quantity;
    }, 0).toFixed(2);
});

const totalDiscount = computed(() => {
    const productDiscount = products.value.reduce((total, item) => {
        // Check if item has a discount
        if (item.discount && item.discount > 0 && item.apply_discount == true) {
            const discountAmount =
                (parseFloat(item.selling_price) - parseFloat(item.discounted_price)) *
                item.quantity;
            return total + discountAmount;
        }
        return total; // If no discount, return total as-is
    }, 0); // Ensures two decimal places

    const couponDiscount = appliedCoupon.value
        ? Number(appliedCoupon.value.discount)
        : 0;

    const orderLevelDiscount = discount.value ? parseFloat(discount.value) : 0;

    return (productDiscount + couponDiscount + orderLevelDiscount).toFixed(2);
});

const validateCustomDiscount = () => {
    if (!custom_discount.value || isNaN(custom_discount.value)) {
        custom_discount.value = 0; // Set default to 0 if the field is empty or invalid
    }
};

const total = computed(() => {
    const subtotalValue = parseFloat(subtotal.value) || 0;
    const discountValue = parseFloat(totalDiscount.value) || 0;
    const customDiscount = parseFloat(custom_discount.value) || 0;
    const guideCash = parseFloat(guide_cash.value) || 0;

    let customValue = 0;

    if (custom_discount_type.value === 'percent') {
        customValue = (subtotalValue * customDiscount) / 100;
    } else if (custom_discount_type.value === 'fixed') {
        customValue = customDiscount;
    }

    return (subtotalValue - discountValue - customValue).toFixed(2);
});

const total_to_include_guide = computed(() => {
    const totalWithGuide = parseFloat(total.value) || 0;
    const guideCash = parseFloat(guide_cash.value) || 0;
    return (totalWithGuide - guideCash).toFixed(2);
});

watch([guide_comi, subtotal], ([newComi, newSubtotal]) => {
    const commissionRate = parseFloat(newComi) || 0;
    const sub = parseFloat(newSubtotal) || 0;
    guide_cash.value = ((commissionRate / 100) * sub).toFixed(2);
});


const balance = computed(() => {
    if (cash.value == null || cash.value === 0) {
        return 0; // If cash.value is null or 0, return 0
    }
    return (parseFloat(cash.value) - parseFloat(total.value)).toFixed(2);
});
// Check for product or handle errors
const form = useForm({
    employee_id: "",
    barcode: "", // Form field for barcode
});

const couponForm = useForm({
    code: "",
});

// Temporary barcode storage during scanning
let barcode = "";
let timeout; // Timeout to detect the end of the scan

const submitCoupon = async () => {
    try {
        const response = await axios.post(route("pos.getCoupon"), {
            code: couponForm.code, // Send the coupon field
        });

        const { coupon: fetchedCoupon, error: fetchedError } = response.data;

        if (fetchedCoupon) {
            appliedCoupon.value = fetchedCoupon;
            products.value.forEach((product) => {
                product.apply_discount = false;
            });
        } else {
            isAlertModalOpen.value = true;
            message.value = fetchedError;
            error.value = fetchedError;
        }
    } catch (err) {
        // console.error(error);
        if (err.response.status === 422) {
            isAlertModalOpen.value = true;
            message.value = err.response.data.message;
        }
    }
};

// Automatically submit the barcode to the backend
const submitBarcode = async () => {
    try {
        // Send POST request to the backend
        const response = await axios.post(route("pos.getProduct"), {
            barcode: form.barcode,
        });

        // Extract the response data
        const { product: fetchedProduct, error: fetchedError } = response.data;

        if (fetchedProduct) {
            if (fetchedProduct.stock_quantity < 1) {
                isAlertModalOpen.value = true;
                message.value = "Product is out of stock";
                return;
            }
            const existingProduct = products.value.find(
                (item) => item.id === fetchedProduct.id
            );

            if (existingProduct) {
                // If it exists, increment the quantity
                existingProduct.quantity += 1;
            } else {
                products.value.push({
                    ...fetchedProduct,
                    quantity: 1,
                    apply_discount: false,
                });
            }

            product.value = fetchedProduct;
            error.value = null;

            shouldFocusCashNext.value = true;
            focusOnCashField();

            console.log(
                "Product fetched successfully and added to cart:",
                fetchedProduct
            );
        } else {
            isAlertModalOpen.value = true;
            message.value = fetchedError;
            error.value = fetchedError;
            console.error("Error:", fetchedError);
        }
    } catch (err) {
        if (err.response.status === 422) {
            isAlertModalOpen.value = true;
            message.value = err.response.data.message;
        }

        console.error("An error occurred:", err.response?.data || err.message);
        error.value = "An unexpected error occurred. Please try again.";
    }
};


// Handle input from the barcode scanner
const handleScannerInput = (event) => {
    clearTimeout(timeout);
    if (event.key === "Enter") {
        form.barcode = barcode;
        submitBarcode();
        barcode = "";
    } else {
        // Append the pressed key to the barcode
        barcode += event.key;
    }

    // Timeout to reset the barcode if scanning is interrupted
    timeout = setTimeout(() => {
        barcode = "";
    }, 100); // Adjust timeout based on scanner speed
};

// Attach the keypress event listener when the component is mounted
onMounted(() => {
    document.addEventListener("keypress", handleScannerInput);
    console.log(props.products);
});

const applyDiscount = (id) => {
    products.value.forEach((product) => {
        if (product.id === id) {
            product.apply_discount = true;
        }
    });
};

const removeDiscount = (id) => {
    products.value.forEach((product) => {
        if (product.id === id) {
            product.apply_discount = false;
        }
    });
};

const handleSelectedProducts = (selectedProducts) => {
    selectedProducts.forEach((fetchedProduct) => {
        const existingProduct = products.value.find(
            (item) => item.id === fetchedProduct.id
        );

        if (existingProduct) {
            // If the product exists, increment its quantity
            existingProduct.quantity += 1;
        } else {
            // If the product doesn't exist, add it with a default quantity
            products.value.push({
                ...fetchedProduct,
                quantity: 1,
                apply_discount: false, // Default additional attribute
            });
        }
    });

    shouldFocusCashNext.value = true;
    focusOnCashField();
};

const handleKeyDown = (event) => {
    const activeElement = document.activeElement;
    const cashInputEl = cashInputRef.value?.$el?.querySelector('input');

    // Prevent accidental form submit on Enter key when focused on Confirm Order
    if (event.key === 'Enter') {
        if (confirmButtonRef.value === activeElement) {
            event.preventDefault();
            confirmButtonRef.value.click(); // Trigger click manually
            return;
        }

        // Prevent accidental enter triggering barcode submission globally
        const tag = activeElement?.tagName?.toLowerCase();
        if (tag === 'input' && activeElement?.id !== 'search') {
            event.preventDefault(); // Don't allow Enter to do anything unless inside search input
        }
    }

    if (event.key === 'Tab') {
        // Force focus to cash input if flagged
        if (shouldFocusCashNext.value) {
            event.preventDefault();
            shouldFocusCashNext.value = false;
            nextTick(() => {
                if (cashInputRef.value?.$el?.querySelector('input')) {
                    cashInputRef.value.$el.querySelector('input').focus();
                }
            });
            return;
        }

        // Tab from cash input to confirm button
        if (activeElement === cashInputEl) {
            event.preventDefault();
            nextTick(() => {
                if (confirmButtonRef.value) {
                    confirmButtonRef.value.focus();
                }
            });
        }
    }
};


// Add event listener on mount
onMounted(() => {
    document.addEventListener("keypress", handleScannerInput);
    document.addEventListener("keydown", handleKeyDown);
    console.log(props.products);
});

// Clean up event listener on unmount
onUnmounted(() => {
    document.removeEventListener("keypress", handleScannerInput);
    document.removeEventListener("keydown", handleKeyDown);
});

const resetToLiveBill = () => {
    selectedOrder.value = null;
    products.value = [];
    cash.value = 0;
    custom_discount.value = 0;
    discount.value = 0;
    selectedStatus.value = "";
    isReturnBill.value = false;
};




const fetchreturn = async () => {
    isReturnMode.value = true;

    if (!form.barcode) {
        isAlertModalOpen.value = true;
        message.value = "Please enter an order ID";
        return;
    }

    try {
        const response = await axios.post(route("pos.getPastOrder"), {
            barcode: form.barcode,
        });

        const { order, error } = response.data;

        if (order) {
            console.log("Received order data:", order);

            // Set the selected order
            selectedOrder.value = order;

            // Set form values
            cash.value = order.cash || 0;
            custom_discount.value = order.custom_discount || 0;
            discount.value = order.discount || 0;
            isReturnBill.value = order.is_return_bill == 1;

            // Handle products
            if (order.products && Array.isArray(order.products)) {
                products.value = order.products.map(product => {
                    return {
                        ...product,
                        quantity: product.pivot?.quantity || 1,
                        selling_price: product.pivot?.price || product.selling_price,
                        apply_discount: false,
                        returnReason: product.pivot?.reason_id || null,
                        pivot: product.pivot // Keep the pivot data for reference
                    };
                });
            } else {
                products.value = [];
                console.error("Products data is not in expected format:", order.products);
            }

            // Set customer information
            customer.value = {
                name: order.customer_name || "",
                contactNumber: order.customer_phone || "",
                email: order.customer_email || ""
            };

            // Set employee ID if available
            employee_id.value = order.employee_id || "";

            // Set payment method
            const paymentMethod = order.payment_method || "cash";
            selectedPaymentMethod.value = paymentMethod.toLowerCase();

            // Parse numeric values
            if (order.cash) {
                cash.value = parseFloat(String(order.cash).replace(/[^0-9.-]+/g, ""));
            }

            if (order.custom_discount) {
                custom_discount.value = parseFloat(String(order.custom_discount).replace(/[^0-9.-]+/g, ""));
            }

            // Default to fixed discount type
            custom_discount_type.value = 'fixed';

        } else {
            isAlertModalOpen.value = true;
            message.value = error || "Order not found";
        }
    } catch (err) {
        console.error("Error fetching past order:", err);

        isAlertModalOpen.value = true;
        message.value = err.response?.data?.error ||
            err.response?.data?.message ||
            "Error loading past order";
    }
};

// Add a computed property to track if any items are being returned
const hasReturnItems = computed(() => {
    if (!selectedOrder.value || !isReturnBill.value) return false;

    return products.value.some(product =>
        product.quantity < (product.pivot?.quantity || product.quantity)
    );
});

// Watch for changes to isReturnBill
watch(isReturnBill, (newValue) => {
    if (newValue && selectedOrder.value) {
        // If turning on return bill mode, make all products need a reason
        products.value.forEach(product => {
            if (!product.returnReason) {
                product.returnReason = "";
            }
        });
    }
});
</script>
