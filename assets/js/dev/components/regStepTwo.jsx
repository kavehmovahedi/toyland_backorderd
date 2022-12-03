import React, {useState} from "react"
import { Button, TextField, Stack, Alert, Autocomplete, FormControl, InputLabel, Select, MenuItem, Menu, Box, Typography, Chip } from "@mui/material"
import {useDropzone} from 'react-dropzone'
import {CloudUploadOutlined} from '@mui/icons-material'

const RegStepTwo = (props) => {
    const [data, _setData] = useState({
        shopName: '',
        registeredCompanyName: '',
        ownerFirstName: '',
        ownerLastName: '',
        phone_1:'',
        phone_1_note:'',
        phone_2: '',
        phone_2_note: '',
        fax: '',
        country: props.defaultCountry,
        state: typeof props.states[props.defaultCountry] == 'object' ? Object.keys(props.states[props.defaultCountry])[0] : '',
        city: '',
        address_1: '',
        address_2: '',
        postcode: '',
        tax_exempt: 'no'
    })
    const [errors, setErrors] = useState({})
    const [fail, setFail] = useState(false)
    const [processing, setProcessing] = useState(false)
    const [currStateList, setCurrStateList] = useState(props.states[props.defaultCountry])
    let states = props.states

    const {acceptedFiles, getInputProps, getRootProps} = useDropzone()

    const updateData = (e) => {
        let _data = data
        _data[e.target.name] = e.target.value
        _setData({..._data})
    }

    const setData =(e, v) => {
        let _data = data
        _data[e] = v
        _setData({..._data})
    }

    const countryChanged = (e, v) => {
        let _data = data
        data['country'] = v
        _setData({..._data})
        if( !states[v] ) {
            let fd = new FormData()
            fd.append('action', 'bhsLoadStates')
            fd.append('csrf', props.server.csrf)
            fd.append('country', v)

            fetch(props.server.url, {
                method: 'POST',
                body: fd
            })
            .then(res => {
                if( res.ok ) return res.json()
                throw new Error('Error communicating with server')
            })
            .then( res => {
                if(res.result == 'success') {
                    states[v] = res.states
                    setCurrStateList(states[v])
                }
            })
            .catch( e => setFail( typeof e == 'object' ? e.message : e))
            .finally( () => {
                if( typeof states[v] == 'object' )
                    setData('state', Object.keys(states[v])[0])
            })
        }
        else {
            setCurrStateList(states[v])
            if( typeof states[v] == 'object' )
                setData('state', Object.keys(states[v])[0])
        }
    }

    const stateChanged = (e, v) => {
        let _data = data
        data['state'] = v
        _setData({..._data})
    }

    const submit = () => {
        setProcessing(true)
        const fd = new FormData()
        fd.append('action', 'js_register')
        fd.append('csrf', props.server.csrf)
        for( const [key, value] of Object.entries(data) )
            fd.append(key, value)
        for( const [_key, _value] of Object.entries(props.data) )
            fd.append(_key, _value)

        if( data.tax_exempt == 'yes' ) {
            fd.append('tax_doc', acceptedFiles[0])
        }
        fetch( props.server.url, {
            method: 'POST',
            body: fd
        })
        .then( res => res.json() )
        .then( res => {
            switch(res.result) {
                case 'error':
                    setFail(false)
                    setErrors(res.errors)
                    break
                case 'success':
                    setFail(false)
                    setErrors({})
                    props.goToStep(2)
                    break
                case 'fail':
                    setFail(res.error)
                    break
            }
        })
        .catch(e => setFail(e))
        .finally( () => setProcessing(false) )
    }

    return(
        <Stack spacing={2}>
            {fail ? <Alert severity="error">{fail}</Alert> : <></>}
            <TextField
                id="shop_name"
                name="shopName"
                label="Shop Name"
                required
                error={errors.shopName ? true : false}
                helperText={errors.shopName}
                value={data.shopName}
                onChange={updateData}
                />
            <TextField
                id="company_name"
                name="registeredCompanyName"
                label="Registered company name"
                error={errors.registeredCompanyName ? true : false}
                helperText={errors.registeredCompanyName}
                value={data.registeredCompanyName}
                onChange={updateData}
                />
            <TextField
                id="first_name"
                name="ownerFirstName"
                label="Owner's first name"
                error={errors.ownerFirstName ? true : false}
                helperText={errors.ownerFirstName}
                value={data.ownerFirstName}
                onChange={updateData}
                required
                />
            <TextField
                id="last_name"
                name="ownerLastName"
                label="Owner's last name"
                error={errors.ownerLastName ? true : false}
                helperText={errors.ownerLastName}
                value={data.ownerLastName}
                onChange={updateData}
                required
                />
            <Autocomplete
                id="user_country"
                name="country"
                label="Country"
                fullWidth
                required
                disableClearable
                value={data.country}
                getOptionLabel={option => props.countriesList[option]}
                options={Object.keys(props.countriesList)}
                onChange={countryChanged}
                renderInput={(params) => <TextField {...params} label="Country" />}
                />
            { currStateList ? (
                <Autocomplete
                    id="user_state"
                    name="state"
                    label="State / Province"
                    fullWidth
                    disableClearable
                    required
                    value={data.state}
                    options={Object.keys(currStateList)}
                    getOptionLabel={option => currStateList[option]}
                    onChange={stateChanged}
                    renderInput={(params) => <TextField {...params} label="State" />}
                    />
                ) : (
                <TextField
                    id="user_state"
                    name="state"
                    label="State / Province"
                    value={data.state}
                    onChange={updateData}
                    />
            )}
            <TextField
                id="city"
                name="city"
                label="City"
                value={data.city}
                onChange={updateData}
                error={errors.city ? true : false}
                helperText={errors.city}
                required
                />
            <TextField
                id="postcode"
                name="postcode"
                label="Zip / Postal Code"
                value={data.postcode}
                onChange={updateData}
                error={errors.postcode ? true : false}
                helperText={errors.postcode}
                required
                />
            <TextField
                id="address_1"
                name="address_1"
                label="Street address: hourse number, street name"
                value={data.address_1}
                onChange={updateData}
                error={errors.address_1 ? true : false}
                helperText={errors.address_1}
                required
                />
            <TextField
                id="address_2"
                name="address_2"
                label="Appartment, suite, unit, etc (optional)"
                value={data.address_2}
                onChange={updateData}
                error={errors.address_2 ? true : false}
                helperText={errors.address_2}
                />
            <TextField
                id="phone_1"
                name="phone_1"
                label="Phone 1"
                error={errors.phone_1 ? true : false}
                helperText={errors.phone_1}
                value={data.phone_1}
                onChange={updateData}
                required
                />
            <TextField
                id="phone_1_note"
                name="phone_1_note"
                label="Phone 1 Note"
                error={errors.phone_1_note ? true : false}
                helperText={errors.phone_1_note}
                value={data.phone_1_note}
                onChange={updateData}
                />
            <TextField
                id="phone_2"
                name="phone_2"
                label="Phone 2"
                error={errors.phone_2 ? true : false}
                helperText={errors.phone_2}
                value={data.phone_2}
                onChange={updateData}
                />
            <TextField
                id="phone_2_note"
                name="phone_2_note"
                label="Phone 2 Note"
                error={errors.phone_2_note ? true : false}
                helperText={errors.phone_2_note}
                value={data.phone_2_note}
                onChange={updateData}
                />
            <TextField
                id="fax"
                name="fax"
                label="Fax"
                error={errors.fax ? true : false}
                helperText={errors.fax}
                value={data.fax}
                onChange={updateData}
                />
            <FormControl fullWidth>
                <InputLabel id="tax_exempt_lbl">Do you have tax exemption?</InputLabel>
                <Select
                    labelId="tax_exempt_lbl"
                    label="Do you have tax exemption?"
                    id="tax_exempt"
                    value={data.tax_exempt}
                    onChange={(e,v) => setData('tax_exempt', v.props.value)}
                    >
                        <MenuItem value="no">No</MenuItem>
                        <MenuItem value="yes">Yes</MenuItem>
                </Select>
            </FormControl>
            {data.tax_exempt == 'yes' ? (
                <Box {...getRootProps()} sx={{p:2, border:'1px dashed grey', borderRadius:'5px'}}>
                    <Stack spacing={2} sx={{textAlign:'center', justifyContent:'center', alignItems:'center'}}>
                        <CloudUploadOutlined fontSize="40px"/>
                        <input {...getInputProps()}/>
                        <Typography>Drag and drop or click / tap to upload document</Typography>
                    </Stack>
                    <div>
                        {acceptedFiles ? acceptedFiles.map( fl => <Chip label={fl.name} key={fl.name}/>) :<></>}
                    </div>
                </Box>
            ) : <></>}
            <Stack direction='row' justifyContent='space-between'>
                <Button
                    variant="outlined"
                    onClick={() => props.goToStep(0)}
                    >
                        Back
                </Button>
                <Button
                    variant="contained"
                    onClick={submit}
                    >
                        Register
                </Button>
            </Stack>
        </Stack>
    )
}

export default RegStepTwo