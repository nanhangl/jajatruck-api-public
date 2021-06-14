var admin = require('firebase-admin');
var jsonwebtoken = require('jsonwebtoken');
var serviceAccount = require("./firebase-adminsdk.json");
const jwtSecret = "REDACTED";

const priceList = {
    "≤ 5kg": "4.30",
    "≤ 10kg": "6.90",
    "≤ 15kg": "9.90",
    "≤ 20kg": "12.90"
};

const jajaPoints = {
    "769098": {
        "name": "Northpoint City",
        "street": "930 Yishun Avenue 2",
        "unit": "#03-14"
    },
    "528523": {
        "name": "Our Tampines Hub",
        "street": "1 Tampines Walk",
        "unit": "#B1-32"
    },
    "098585": {
        "name": "VivoCity",
        "street": "1 HarbourFront Walk",
        "unit": "#01-23A"
    },
    "648886": {
        "name": "Jurong Point",
        "street": "1 Jurong West Central 2",
        "unit": "#02-44"
    }
}

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();
const usersRef = db.collection('users');
const deliveriesRef = db.collection('deliveries');

function checkToken(token) {
    const result = jsonwebtoken.verify(token, jwtSecret);
    if (result) {
        return result;
    } else {
        return false;
    }
}

module.exports = async (req, res) => {
    switch (req.body.endpoint) {
        case 'checkExists': {
            const doc = await usersRef.doc(`+65${req.body.phone}`).get();
            if (!doc.exists) {
                res.send({status:'ok', result:'notExist'});
            } else {
                res.send({status:'ok', result:doc.data().status});
            }
            break;           
        }
        
        case 'verify': {
            //admin.auth().verifyIdToken(req.body.idToken).then(async decodedToken => {
                const decodedToken = req.body.mockDecodedToken;
                const doc = await usersRef.doc(decodedToken.phone_number).get();
                if (!doc.exists) {
                    usersRef.doc(decodedToken.phone_number).set({
                        "fName": req.body.fName,
                        "lName": req.body.lName,
                        "status": "active",
                        "credit": 0.00
                    })
                    const jwToken = jsonwebtoken.sign({
                        "fName": req.body.fName,
                        "lName": req.body.lName,
                        "phone": decodedToken.phone_number,
                        "pointCode": '0',
                        "driverCode": '0'
                    }, jwtSecret)
                    res.send({"status":"ok", "token":jwToken});
                } else {
                    const docData = doc.data();
                    const jwToken = jsonwebtoken.sign({
                        "fName": docData.fName,
                        "lName": docData.lName,
                        "phone": decodedToken.phone_number,
                        "pointCode": docData.pointCode || '0',
                        "driverCode": docData.driverCode || '0'
                    }, jwtSecret)
                    res.send({"status":"ok", "token":jwToken});
                }
            //});
            break;
        }

        case 'checkToken': {
            if (checkToken(req.body.token)) {
                res.send({"status":"ok"});
            } else {
                res.send({"status":"err"});
            }
            break;
        }

        case 'addAddress': {
            const userInfo = checkToken(req.body.token);
            const { street, unit, postal, mobile } = req.body.data;
            await usersRef.doc(userInfo.phone).collection("addresses").add({
                "street": street,
                "unit": unit,
                "postal": postal
            })
            res.send({"status":"ok"});
            break;
        }

        case 'getAddresses': {
            const userInfo = checkToken(req.body.token);
            const collection = await usersRef.doc(userInfo.phone).collection("addresses").get();
            var addresses = {};
            collection.forEach(doc => {
                addresses[doc.id] = doc.data();
            })
            res.send({"status":"ok","addresses":addresses});
            break;
        }

        case 'createDelivery': {
            const userInfo = checkToken(req.body.token);
            const returnAddress = await usersRef.doc(userInfo.phone).collection("addresses").doc(req.body.address).get();
            await deliveriesRef.add({
                "sender": {
                    "name": `${userInfo.fName} ${userInfo.lName}`,
                    "mobile": userInfo.phone,
                    "address": returnAddress.data()
                },
                "recipient": {
                    "name": req.body.rName,
                    "mobile": req.body.rMobile,
                    "address": req.body.rAddress
                },
                "method": req.body.method,
                "dropOff": req.body.dropOff,
                "timeFrame": req.body.timeFrame,
                "weight": req.body.weight,
                "deliveryInstructions": req.body.deliveryInstructions,
                "status": {
                    "current": "toPay",
                    "toPay": new Date().toISOString()
                }
            })
            res.send({"status":"ok"})
            break;
        }

        case 'getUnpaidOrders': {
            const userInfo = checkToken(req.body.token);
            const allDeliveries = await deliveriesRef.get();
            var unpaidOrders = [];
            allDeliveries.forEach(async doc => {
                const data = doc.data();
                if (data.sender.mobile == userInfo.phone && data.status.current == "toPay") {
                    unpaidOrders.push({
                        "id": doc.id,
                        "selected": "checked",
                        "recipient": data.recipient,
                        "method": data.method,
                        "weight": data.weight,
                        "dropOff": data.dropOff
                    })
                }
            })
            const userFields = await usersRef.doc(userInfo.phone).get();
            const credit = await userFields.data().credit;
            res.send({"status":"ok","unpaidOrders":unpaidOrders,"credit":credit});
            break;
        }

        case 'checkout': {
            const userInfo = checkToken(req.body.token);
            const userFields = await usersRef.doc(userInfo.phone).get();
            const credit = await userFields.data().credit;
            const orders = req.body.orders;
            const date = new Date().toISOString();
            var total = 0;
            for (var i in orders) {
                const order = await deliveriesRef.doc(orders[i]).get();
                const weight = order.data().weight;
                total += parseFloat(priceList[weight]);
            }
            if (total < credit) {
                await usersRef.doc(userInfo.phone).update({
                    "credit": credit - total
                });
                for (var i in orders) {
                    await deliveriesRef.doc(orders[i]).set({
                        "status": {
                            "current": "toPickUpDropOff",
                            "toPickUpDropOff": {
                                "date": date
                            }
                        }
                    }, {merge: true});
                }
                res.send({"status":"ok"});
            } else {
                res.send({"status":"err"});
            }
            break;
        }

        case 'getShipperDeliveries': {
            const userInfo = checkToken(req.body.token);
            const allDeliveries = await deliveriesRef.get();
            var toPay = [];
            var toPickUpDropOff = [];
            var withinJajaTruck = [];
            var completed = [];
            var cancelled = [];
            allDeliveries.forEach(async doc => {
                if (doc.data().sender.mobile == userInfo.phone) {
                    switch (doc.data().status.current) {
                        case 'toPay': {
                            const docData = {
                                "id": doc.id,
                                "data": doc.data()
                            };
                            toPay.push(docData);
                            break;
                        }
                        
                        case 'toPickUpDropOff': {
                            const docData = {
                                "id": doc.id,
                                "data": doc.data()
                            };
                            toPickUpDropOff.push(docData);
                            break;
                        }

                        case 'toDeliver': {
                            const docData = {
                                "id": doc.id,
                                "data": doc.data()
                            };
                            toDeliver.push(docData);
                            break;
                        }

                        case 'withinJajaTruck': {
                            const docData = {
                                "id": doc.id,
                                "data": doc.data()
                            };
                            withinJajaTruck.push(docData);
                            break;
                        }


                        case 'completed': {
                            const docData = {
                                "id": doc.id,
                                "data": doc.data()
                            };
                            completed.push(docData);
                            break;
                        }

                        case 'cancelled': {
                            const docData = {
                                "id": doc.id,
                                "data": doc.data()
                            };
                            cancelled.push(docData);
                            break;
                        }
                    }
                }
            })
            res.send({
                "status": "ok",
                "deliveries": {
                    "toPay": toPay,
                    "toPickUpDropOff": toPickUpDropOff,
                    "withinJajaTruck": withinJajaTruck,
                    "completed": completed,
                    "cancelled": cancelled
                }
            });
            break;
        }

        case 'getPointParcels': {
            const userInfo = checkToken(req.body.token);
            const toBeCollected = await deliveriesRef.where('status.withinJajaTruck.current', '==', 'readyForCollection').where('status.current', '==', 'withinJajaTruck').where('recipient.address.postal', '==', userInfo.pointCode).get();
            const receivedInStore = await deliveriesRef.where('status.current', '==', 'withinJajaTruck').where('status.withinJajaTruck.current', '==', 'pickedUpDroppedOff').where('status.withinJajaTruck.pickedUpDroppedOff.acceptedBy', '==', `P${userInfo.pointCode}`).get();
            var toBeCollectedArray = [];
            var receivedInStoreArray = [];
            toBeCollected.forEach(doc => {
                toBeCollectedArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            })
            receivedInStore.forEach(doc => {
                receivedInStoreArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            })
            res.send({
                "status": "ok",
                "parcels": {
                    "toBeCollected": toBeCollectedArray,
                    "receivedInStore": receivedInStoreArray
                }
            });
            break;
        }

        case 'pointCheckDeliveryId': {
            const userInfo = checkToken(req.body.token);
            const delivery = await deliveriesRef.doc(req.body.deliveryId).get();
            if (delivery.exists) {
                const docData = delivery.data();
                if (docData.status.current == "toPickUpDropOff" && docData.dropOff == userInfo.pointCode) {
                    res.send({"status":"ok","data":docData});
                } else {
                    res.send({"status":"invalid"});
                }
            } else {
                res.send({"status":"invalid"});
            }
            break;
        }

        case 'pointAcceptParcel': {
            const userInfo = checkToken(req.body.token);
            const delivery = await deliveriesRef.doc(req.body.deliveryId).get();
            if (delivery.exists) {
                const docData = delivery.data();
                if (docData.status.current == "toPickUpDropOff" && docData.dropOff == userInfo.pointCode) {
                    if (docData.method == "Drop-off" && docData.recipient.address.jajaPoint && docData.dropOff == docData.recipient.address.postal) {
                        await deliveriesRef.doc(req.body.deliveryId).set({
                            "status": {
                                "current": "withinJajaTruck",
                                "withinJajaTruck": {
                                    "current": "readyForCollection",
                                    "readyForCollection": {
                                        "date": new Date().toISOString()
                                    }
                                }
                            }
                        }, {merge: true});
                    } else {
                        await deliveriesRef.doc(req.body.deliveryId).set({
                            "status": {
                                "current": "withinJajaTruck",
                                "withinJajaTruck": {
                                    "current": "pickedUpDroppedOff",
                                    "pickedUpDroppedOff": {
                                        "date": new Date().toISOString(),
                                        "acceptedBy": `P${userInfo.pointCode}`
                                    }
                                }
                            }
                        }, {merge: true});
                    }
                    res.send({"status":"ok"});
                } else {
                    res.send({"status":"invalid"});
                }
            } else {
                res.send({"status":"invalid"});
            }
            break;
        }

        case 'getDriverParcels': {
            const userInfo = checkToken(req.body.token);
            const deliveries = await deliveriesRef.where('status.current', '==', 'withinJajaTruck').where('status.withinJajaTruck.current', '==', 'onVehicleForDelivery').where('status.withinJajaTruck.onVehicleForDelivery.driver', '==', userInfo.phone).orderBy('order').get();
            const pointPickUp = await deliveriesRef.where('status.current', '==', 'withinJajaTruck').where('status.withinJajaTruck.current', '==', 'waitingForPickUp').where('status.withinJajaTruck.waitingForPickUp.driver', '==', userInfo.phone).orderBy('order').get();
            const customerPickUp = await deliveriesRef.where('status.current', '==', 'toPickUpDropOff').where('status.toPickUpDropOff.driver', '==', userInfo.phone).orderBy('order').get();
            var deliveriesArray = [];
            var pickUpsArray = [];
            var allParcelsArray = [];
            deliveries.forEach(doc => {
                deliveriesArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
                allParcelsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            });
            pointPickUp.forEach(doc => {
                pickUpsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
                allParcelsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            });
            customerPickUp.forEach(doc => {
                pickUpsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
                allParcelsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            });
            allParcelsArray.sort((a, b) => (a.data.order > b.data.order) ? 1 : ((b.data.order > a.data.order) ? -1 : 0))
            const parcels = {
                "all": allParcelsArray,
                "deliveries": deliveriesArray,
                "pickUps": pickUpsArray
            };
            res.send({"status":"ok", "parcels":parcels});
            break;
        }

        case 'driverParcelDetails': {
            const userInfo = checkToken(req.body.token);
            if (userInfo.driverCode != '0') {
                const delivery = await deliveriesRef.doc(req.body.deliveryId).get();
                res.send({"status":"ok", "details":delivery.data()});
            } else {
                res.send({"status":"err"});
            }
            break;
        }

        case 'driverParcelAction': {
            const userInfo = checkToken(req.body.token);
            const deliveryId = req.body.deliveryId;
            const action = req.body.action;
            const doc = await deliveriesRef.doc(deliveryId).get();
            const docData = doc.data();
            if (action == "pickup") {
                if (docData.status.current == "toPickUpDropOff") {
                    await deliveriesRef.doc(deliveryId).set({
                        "status": {
                            "current": "withinJajaTruck",
                            "withinJajaTruck": {
                                "current": "enrouteToWarehouse",
                                "pickedUpDroppedOff": {
                                    "acceptedBy": `D${userInfo.driverCode}`,
                                    "date": new Date().toISOString()
                                },
                                "enrouteToWarehouse": {
                                    "driver": userInfo.phone,
                                    "date": new Date().toISOString()
                                }
                            }
                        }
                    }, {merge:true})
                } else if (docData.status.current == "withinJajaTruck") {
                    await deliveriesRef.doc(deliveryId).set({
                        "status": {
                            "withinJajaTruck": {
                                "current": "enrouteToWarehouse",
                                "enrouteToWarehouse": {
                                    "driver": userInfo.phone,
                                    "date": new Date().toISOString()
                                }
                            }
                        }
                    }, {merge:true})
                }
            } else if (action == "deliver") {
                if (docData.recipient.address.jajaPoint) {
                    await deliveriesRef.doc(deliveryId).set({
                        "status": {
                            "withinJajaTruck": {
                                "current": "readyForCollection",
                                "readyForCollection": {
                                    "date": new Date().toISOString()
                                }
                            }
                        }
                    }, {merge:true})
                } else {
                    await deliveriesRef.doc(deliveryId).set({
                        "status": {
                            "current": "completed",
                            "completed": {
                                "date": new Date().toISOString()
                            }
                        }
                    }, {merge:true})
                }
            }
            res.send({"status":"ok"});
            break;
        }

        case 'warehouseRegisterParcel': {
            const warehouseInfo = checkToken(req.body.token);
            const deliveryId = req.body.deliveryId;
            await deliveriesRef.doc(deliveryId).set({
                "status": {
                    "withinJajaTruck": {
                        "current": "inWarehouse",
                        "inWarehouse": {
                            "date": new Date().toISOString(),
                            "warehouse": warehouseInfo.warehouse
                        }
                    }
                }
            }, {merge:true});
            res.send({"status":"ok"});
            break;
        }

        case 'driverHistory': {
            const userInfo = checkToken(req.body.token);
            const pickups = await deliveriesRef.where("status.withinJajaTruck.enrouteToWarehouse.driver", "==", userInfo.phone).limit(20).get();
            const deliveries = await deliveriesRef.where("status.current", "==", "completed").where("status.withinJajaTruck.onVehicleForDelivery.driver", "==", userInfo.phone).limit(20).get();
            const all = [];
            pickups.forEach(doc => {
                all.push({
                    "id": doc.id,
                    "type": "pickup",
                    "data": doc.data(),
                    "date": doc.data().status.withinJajaTruck.enrouteToWarehouse.date
                })
            })
            deliveries.forEach(doc => {
                all.push({
                    "id": doc.id,
                    "type": "delivery",
                    "data": doc.data(),
                    "date": doc.data().status.withinJajaTruck.onVehicleForDelivery.date
                })
            })
            all.sort((a,b) => b.date - a.date)
            res.send({"status":"ok", "history": all});
            break;
        }

        case 'warehouseGetDriverParcels': {
            const userInfo = checkToken(req.body.token);
            const driverId = `+65${req.body.driverId}`;
            const deliveries = await deliveriesRef.where('status.current', '==', 'withinJajaTruck').where('status.withinJajaTruck.current', '==', 'onVehicleForDelivery').where('status.withinJajaTruck.onVehicleForDelivery.driver', '==', driverId).orderBy('order').get();
            const pointPickUp = await deliveriesRef.where('status.current', '==', 'withinJajaTruck').where('status.withinJajaTruck.current', '==', 'waitingForPickUp').where('status.withinJajaTruck.waitingForPickUp.driver', '==', driverId).orderBy('order').get();
            const customerPickUp = await deliveriesRef.where('status.current', '==', 'toPickUpDropOff').where('status.toPickUpDropOff.driver', '==', driverId).orderBy('order').get();
            var deliveriesArray = [];
            var pickUpsArray = [];
            var allParcelsArray = [];
            deliveries.forEach(doc => {
                deliveriesArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
                allParcelsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            });
            pointPickUp.forEach(doc => {
                pickUpsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
                allParcelsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            });
            customerPickUp.forEach(doc => {
                pickUpsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
                allParcelsArray.push({
                    "id": doc.id,
                    "data": doc.data()
                });
            });
            allParcelsArray.sort((a, b) => (a.data.order > b.data.order) ? 1 : ((b.data.order > a.data.order) ? -1 : 0))
            res.send({"status":"ok", "parcels":allParcelsArray});
            break;
        }
    }
}
